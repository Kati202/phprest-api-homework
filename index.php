<?php
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 'On');

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Adatbázis kapcsolat
$host = 'localhost';
$db   = 'shopping_cart';
$user = 'root';
$pass = 'mysql';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'POST':
        if (isset($_GET['sell'])) {
            sellItem($pdo);
        } else {
            addItem($pdo);
        }
        break;
    case 'GET':
        if (isset($_GET['summary'])) {
            getSummary($pdo);
        } elseif (isset($_GET['id'])) {
            $id = htmlspecialchars(strip_tags($_GET['id']));
            getItemById($pdo, $id);
        } elseif (isset($_GET['sold'])) {
            getSoldItems($pdo);
        } else {
            getItems($pdo);
        }
        break;
    case 'PUT':
        updateItem($pdo);
        break;
    case 'DELETE':
        deleteItem($pdo);
        break;
    default:
        http_response_code(405);
        echo json_encode(["message" => "Method not allowed"]);
        break;
}

// Elem hozzáadása
function addItem($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!isset($input['name']) || !isset($input['price']) || !isset($input['quantity'])) {
        http_response_code(400);
        echo json_encode(["message" => "Invalid input"]);
        return;
    }

    $sql = "INSERT INTO items (name, price, quantity) VALUES (?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$input['name'], $input['price'], $input['quantity']]);
    http_response_code(201);
    echo json_encode(["message" => "Item added"]);
}

// Termékek lekérdezése
function getItems($pdo) {
    $sql = "SELECT * FROM items";
    $stmt = $pdo->query($sql);
    $items = $stmt->fetchAll();
    echo json_encode($items);
}

// Eladott termékek lekérdezése
function getSoldItems($pdo) {           
    $sql = "SELECT * FROM sold_items";
    $stmt = $pdo->query($sql);
    $items = $stmt->fetchAll();
    echo json_encode($items);
}
// Termék lekérdezése ID alapján
function getItemById($pdo, $id) {
    $sql = "SELECT * FROM items WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $item = $stmt->fetch();
    if ($item) {
        http_response_code(200);
        echo json_encode($item);
    } else {
        http_response_code(404);
        echo json_encode(["message" => "Item not found"]);
    }
}

// Termék eladása
function sellItem($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!isset($input['id']) || !isset($input['quantity'])) {
        http_response_code(400);
        echo json_encode(["message" => "Invalid input"]);
        return;
    }

    // Ellenőrzi, hogy a termék létezik-e és elegendő mennyiség áll-e rendelkezésre
    $sql = "SELECT * FROM items WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$input['id']]);
    $item = $stmt->fetch();

    if (!$item) {
        http_response_code(404);
        echo json_encode(["message" => "Item not found"]);
        return;
    }

    if ($item['quantity'] < $input['quantity']) {
        http_response_code(400);
        echo json_encode(["message" => "Insufficient quantity"]);
        return;
    }

    //Hozzáadja az eladott terméket az sold_items táblához
    $sql = "INSERT INTO sold_items (item_id, name, price, quantity) VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$item['id'], $item['name'], $item['price'], $input['quantity']]);

    // Csökkenti a termék mennyiségét az items táblában
    $sql = "UPDATE items SET quantity = quantity - ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$input['quantity'], $input['id']]);

    http_response_code(201);
    echo json_encode(["message" => "Item sold"]);
}

// Összesített adatok lekérdezése
function getSummary($pdo) {
    $sql = "SELECT COUNT(*) as item_count, SUM(price * quantity) as total_price FROM items";
    $stmt = $pdo->query($sql);
    $summary = $stmt->fetch();
    echo json_encode($summary);
}

// Termék frissítése
function updateItem($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!isset($input['id']) || !isset($input['name']) || !isset($input['price']) || !isset($input['quantity'])) {
        http_response_code(400);
        echo json_encode(["message" => "Invalid input"]);
        return;
    }

    $sql = "UPDATE items SET name = ?, price = ?, quantity = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$input['name'], $input['price'], $input['quantity'], $input['id']]);
    http_response_code(200);
    echo json_encode(["message" => "Item updated"]);
}

// Termék törlése
function deleteItem($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!isset($input['id'])) {
        http_response_code(400);
        echo json_encode(["message" => "Invalid input"]);
        return;
    }

    $sql = "DELETE FROM items WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$input['id']]);
    http_response_code(200);
    echo json_encode(["message" => "Item deleted"]);
}
?>
