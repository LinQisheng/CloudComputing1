<?php
// api.php
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

require_once __DIR__ . '/db.php';
session_start();

/* helpers */
function body_json() {
  $raw = file_get_contents('php://input');
  $data = json_decode($raw, true);
  return is_array($data) ? $data : [];
}
function require_auth() {
  if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
  }
}

$path = $_GET['path'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

/* -------- AUTH -------- */
if ($path === 'auth/register' && $method === 'POST') {
  $d = body_json();
  $name = trim($d['name'] ?? '');
  $email = strtolower(trim($d['email'] ?? ''));
  $pass = $d['password'] ?? '';
  if (!$name || !$email || !$pass) { http_response_code(422); echo json_encode(['error'=>'Datos incompletos']); exit; }

  $stmt = $mysqli->prepare('SELECT id FROM users WHERE email=?');
  $stmt->bind_param('s', $email);
  $stmt->execute();
  $stmt->store_result();
  if ($stmt->num_rows > 0) { http_response_code(409); echo json_encode(['error'=>'Email ya registrado']); exit; }
  $stmt->close();

  $hash = password_hash($pass, PASSWORD_DEFAULT);
  $role = 'user';
  $stmt = $mysqli->prepare('INSERT INTO users(name,email,password_hash,role) VALUES (?,?,?,?)');
  $stmt->bind_param('ssss', $name, $email, $hash, $role);
  $ok = $stmt->execute();
  echo json_encode(['ok'=>$ok, 'id'=>$mysqli->insert_id]); exit;
}

if ($path === 'auth/login' && $method === 'POST') {
  $d = body_json();
  $email = strtolower(trim($d['email'] ?? ''));
  $pass  = $d['password'] ?? '';
  $stmt = $mysqli->prepare('SELECT id,name,email,password_hash,role FROM users WHERE email=?');
  $stmt->bind_param('s', $email);
  $stmt->execute();
  $res = $stmt->get_result();
  $u = $res->fetch_assoc();
  if (!$u || !password_verify($pass, $u['password_hash'])) { http_response_code(401); echo json_encode(['error'=>'Credenciales inválidas']); exit; }
  $_SESSION['user'] = ['id'=>$u['id'],'name'=>$u['name'],'email'=>$u['email'],'role'=>$u['role']];
  echo json_encode(['user'=>$_SESSION['user']]); exit;
}

if ($path === 'auth/me' && $method === 'GET') { echo json_encode(['user'=>($_SESSION['user'] ?? null)]); exit; }
if ($path === 'auth/logout' && $method === 'POST') { session_destroy(); echo json_encode(['ok'=>true]); exit; }

/* -------- PROJECTS -------- */
if ($path === 'projects' && $method === 'GET') {
  require_auth();
  $uid = $_SESSION['user']['id'];
  $sql = 'SELECT p.* FROM projects p WHERE p.owner_id=? OR EXISTS (SELECT 1 FROM tasks t WHERE t.project_id=p.id AND (t.assignee_id=? OR t.created_by=?)) ORDER BY p.created_at DESC';
  $stmt = $mysqli->prepare($sql);
  $stmt->bind_param('iii', $uid,$uid,$uid);
  $stmt->execute();
  echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC)); exit;
}

if ($path === 'projects' && $method === 'POST') {
  require_auth();
  $d = body_json();
  $name = trim($d['name'] ?? '');
  if (!$name) { http_response_code(422); echo json_encode(['error'=>'Nombre requerido']); exit; }
  $desc = $d['description'] ?? null;
  $owner = $_SESSION['user']['id'];
  $stmt = $mysqli->prepare('INSERT INTO projects(name,description,owner_id) VALUES (?,?,?)');
  $stmt->bind_param('ssi', $name,$desc,$owner);
  $stmt->execute();
  echo json_encode(['id'=>$mysqli->insert_id]); exit;
}

if (preg_match('#^projects/(\d+)$#', $path, $m)) {
  $pid = (int)$m[1];
  if ($method === 'PUT' || $method === 'PATCH') {
    require_auth();
    $d = body_json();
    $name = trim($d['name'] ?? '');
    $desc = $d['description'] ?? null;
    $stmt = $mysqli->prepare('UPDATE projects SET name=?, description=? WHERE id=?');
    $stmt->bind_param('ssi', $name,$desc,$pid);
    $stmt->execute(); echo json_encode(['ok'=>true]); exit;
  }
  if ($method === 'DELETE') {
    require_auth();
    $stmt = $mysqli->prepare('DELETE FROM projects WHERE id=?');
    $stmt->bind_param('i', $pid);
    $stmt->execute(); echo json_encode(['ok'=>true]); exit;
  }
}

/* -------- TASKS -------- */
if ($path === 'tasks' && $method === 'GET') {
  require_auth();
  $project = isset($_GET['project_id']) ? (int)$_GET['project_id'] : null;
  $sql = 'SELECT t.*, u.name AS assignee_name FROM tasks t LEFT JOIN users u ON u.id=t.assignee_id';
  if ($project) { $sql .= ' WHERE t.project_id='.(int)$project; }
  $sql .= " ORDER BY FIELD(status,'pendiente','en_progreso','done'), priority DESC, due_date IS NULL, due_date ASC, created_at DESC";
  $res = $mysqli->query($sql);
  echo json_encode($res->fetch_all(MYSQLI_ASSOC)); exit;
}

if ($path === 'tasks' && $method === 'POST') {
  require_auth();
  $d = body_json();
  $pid = (int)($d['project_id'] ?? 0);
  $title = trim($d['title'] ?? '');
  if (!$pid || !$title) { http_response_code(422); echo json_encode(['error'=>'project_id y title requeridos']); exit; }
  $desc = $d['description'] ?? null;
  $status = $d['status'] ?? 'pendiente';
  $prio = $d['priority'] ?? 'media';
  $due = $d['due_date'] ?? null;
  $assignee = isset($d['assignee_id']) ? (int)$d['assignee_id'] : null;
  $creator = $_SESSION['user']['id'];

  $stmt = $mysqli->prepare('INSERT INTO tasks(project_id,title,description,status,priority,due_date,assignee_id,created_by) VALUES (?,?,?,?,?,?,?,?)');
  $stmt->bind_param('issssssi', $pid,$title,$desc,$status,$prio,$due,$assignee,$creator);
  $stmt->execute();
  echo json_encode(['id'=>$mysqli->insert_id]); exit;
}

if (preg_match('#^tasks/(\d+)$#', $path, $m)) {
  $tid = (int)$m[1];
  if ($method === 'PUT' || $method === 'PATCH') {
    require_auth();
    $d = body_json();

    /* Soporta PATCH parcial: mantiene valores actuales si no llegan */
    $stmt = $mysqli->prepare('SELECT title,description,status,priority,due_date,assignee_id FROM tasks WHERE id=?');
    $stmt->bind_param('i', $tid);
    $stmt->execute();
    $cur = $stmt->get_result()->fetch_assoc();
    if (!$cur) { http_response_code(404); echo json_encode(['error'=>'Tarea no encontrada']); exit; }

    $title = array_key_exists('title',$d) ? trim((string)$d['title']) : $cur['title'];
    $desc  = array_key_exists('description',$d) ? $d['description'] : $cur['description'];
    $status= array_key_exists('status',$d) ? $d['status'] : $cur['status'];
    $prio  = array_key_exists('priority',$d) ? $d['priority'] : $cur['priority'];
    $due   = array_key_exists('due_date',$d) ? $d['due_date'] : $cur['due_date'];
    $assignee = array_key_exists('assignee_id',$d) ? $d['assignee_id'] : $cur['assignee_id'];

    $stmt = $mysqli->prepare('UPDATE tasks SET title=?, description=?, status=?, priority=?, due_date=?, assignee_id=? WHERE id=?');
    $stmt->bind_param('ssssssi', $title,$desc,$status,$prio,$due,$assignee,$tid);
    $stmt->execute(); echo json_encode(['ok'=>true]); exit;
  }
  if ($method === 'DELETE') {
    require_auth();
    $stmt = $mysqli->prepare('DELETE FROM tasks WHERE id=?');
    $stmt->bind_param('i', $tid);
    $stmt->execute(); echo json_encode(['ok'=>true]); exit;
  }
}

/* -------- COMMENTS -------- */
if (preg_match('#^tasks/(\d+)/comments$#', $path, $m)) {
  $tid = (int)$m[1];
  if ($method === 'GET') {
    $stmt = $mysqli->prepare('SELECT c.*, u.name AS author FROM task_comments c JOIN users u ON u.id=c.user_id WHERE c.task_id=? ORDER BY c.created_at ASC');
    $stmt->bind_param('i', $tid);
    $stmt->execute();
    echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC)); exit;
  }
  if ($method === 'POST') {
    require_auth();
    $d = body_json();
    $content = trim($d['content'] ?? '');
    if (!$content) { http_response_code(422); echo json_encode(['error'=>'contenido requerido']); exit; }
    $uid = $_SESSION['user']['id'];
    $stmt = $mysqli->prepare('INSERT INTO task_comments(task_id,user_id,content) VALUES (?,?,?)');
    $stmt->bind_param('iis', $tid,$uid,$content);
    $stmt->execute(); echo json_encode(['id'=>$mysqli->insert_id]); exit;
  }
}

/* 404 por defecto */
http_response_code(404);
echo json_encode(['error'=>'Endpoint no encontrado','path'=>$path,'method'=>$method]);
