<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['error' => 'Method not allowed']);
  exit;
}

$root = realpath(__DIR__ . '/..');
$jsonFile = $root . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'users.json';
if (!file_exists($jsonFile)) { @file_put_contents($jsonFile, json_encode([])); }

function read_json($path) {
  $raw = @file_get_contents($path);
  $data = $raw ? json_decode($raw, true) : [];
  return is_array($data) ? $data : [];
}
function sanitize_text($value) { return trim(filter_var($value ?? '', FILTER_SANITIZE_STRING)); }

$payload = null;
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (stripos($contentType, 'application/json') !== false) {
  $payload = json_decode(file_get_contents('php://input'), true);
}
if (!is_array($payload)) { $payload = $_POST; }

$email = strtolower(sanitize_text($payload['email'] ?? ''));
$password = (string)($payload['password'] ?? '');
if ($email === '' || $password === '') {
  http_response_code(400);
  echo json_encode(['error' => 'Email et mot de passe requis.']);
  exit;
}

$users = read_json($jsonFile);
$user = null;
foreach ($users as $u) {
  if (isset($u['email']) && strtolower($u['email']) === $email) { $user = $u; break; }
}
if (!$user || !isset($user['password']) || $user['password'] !== $password) {
  http_response_code(401);
  echo json_encode(['error' => 'Identifiants invalides.']);
  exit;
}

$safeUser = $user;
unset($safeUser['password']);
echo json_encode(['ok' => true, 'user' => $safeUser]);
exit;



