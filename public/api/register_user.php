<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['error' => 'Method not allowed']);
  exit;
}

$root = realpath(__DIR__ . '/..'); // public
$dataDir = $root . DIRECTORY_SEPARATOR . 'data';
$jsonFile = $dataDir . DIRECTORY_SEPARATOR . 'users.json';
if (!is_dir($dataDir)) { @mkdir($dataDir, 0777, true); }
if (!file_exists($jsonFile)) { @file_put_contents($jsonFile, json_encode([])); }

function read_json($path) {
  $raw = @file_get_contents($path);
  $data = $raw ? json_decode($raw, true) : [];
  return is_array($data) ? $data : [];
}
function write_json($path, $data) {
  @file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}
function sanitize_text($value) { return trim(filter_var($value ?? '', FILTER_SANITIZE_STRING)); }

// Accept JSON body or form
$payload = null;
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (stripos($contentType, 'application/json') !== false) {
  $payload = json_decode(file_get_contents('php://input'), true);
}
if (!is_array($payload)) { $payload = $_POST; }

$role = sanitize_text($payload['role'] ?? '');
$firstName = sanitize_text($payload['firstName'] ?? '');
$lastName = sanitize_text($payload['lastName'] ?? '');
$email = strtolower(sanitize_text($payload['email'] ?? ''));
$password = (string)($payload['password'] ?? '');
$phone = sanitize_text($payload['phone'] ?? '');
$location = sanitize_text($payload['location'] ?? '');
$address = sanitize_text($payload['address'] ?? '');
$country = sanitize_text($payload['country'] ?? '');

if ($role === '' || $email === '' || $password === '' || $firstName === '' || $lastName === '') {
  http_response_code(400);
  echo json_encode(['error' => 'Champs requis manquants.']);
  exit;
}

$users = read_json($jsonFile);
foreach ($users as $u) {
  if (isset($u['email']) && strtolower($u['email']) === $email) {
    http_response_code(409);
    echo json_encode(['error' => 'Un compte existe déjà avec cet email.']);
    exit;
  }
}

$now = date('c');
$user = [
  'id' => round(microtime(true) * 1000),
  'role' => $role,
  'firstName' => $firstName,
  'lastName' => $lastName,
  'email' => $email,
  'password' => $password, // DEMO ONLY: do not store plain text in production
  'phone' => $phone,
  'location' => $location,
  'address' => $address,
  'country' => $country,
  'createdAt' => $now,
  'updatedAt' => $now
];

$users[] = $user;
write_json($jsonFile, $users);

$safeUser = $user;
unset($safeUser['password']);
echo json_encode(['ok' => true, 'user' => $safeUser]);
exit;


