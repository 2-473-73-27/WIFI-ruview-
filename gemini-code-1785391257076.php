<?php
/**
 * COMPLETE SINGLE-FILE E-COMMERCE ENGINE
 * Features: Client Storefront, Shopping Cart, Checkout, Order Tracking, 
 *           Product Detail Views, Search/Filter, and Full Manager Dashboard.
 * Database: SQLite (Auto-creates 'store.db' on first run - no SQL setup required).
 */

session_start();

// ==========================================
// 1. DATABASE INITIALIZATION & SETUP
// ==========================================
try {
    $db = new PDO('sqlite:store.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Schema Creation
    $db->exec("
        CREATE TABLE IF NOT EXISTS products (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            category TEXT NOT NULL,
            price REAL NOT NULL,
            rating REAL DEFAULT 4.5,
            description TEXT,
            image_url TEXT
        );

        CREATE TABLE IF NOT EXISTS orders (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            customer_name TEXT NOT NULL,
            customer_email TEXT NOT NULL,
            shipping_address TEXT NOT NULL,
            total_amount REAL NOT NULL,
            status TEXT DEFAULT 'Pending',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS order_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            order_id INTEGER NOT NULL,
            product_name TEXT NOT NULL,
            price REAL NOT NULL,
            quantity INTEGER NOT NULL,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
        );
    ");

    // Seed Sample Catalog
    $product_count = $db->query("SELECT COUNT(*) FROM products")->fetchColumn();
    if ($product_count == 0) {
        $seed_stmt = $db->prepare("INSERT INTO products (name, category, price, rating, description, image_url) VALUES (?, ?, ?, ?, ?, ?)");
        $sample_products = [
            ['Wireless Noise-Canceling Headphones', 'Electronics', 149.99, 4.7, 'High-fidelity audio with active noise cancellation and 30-hour battery life.', 'https://picsum.photos/seed/headphones/300/300'],
            ['Smartwatch Series 7 Waterproof', 'Electronics', 229.00, 4.5, 'Fitness tracking, heart rate monitoring, and seamless phone integration.', 'https://picsum.photos/seed/watch/300/300'],
            ['Ergonomic Mesh Office Chair', 'Home & Office', 189.50, 4.3, 'Adjustable lumbar support with breathable mesh back and padded seat.', 'https://picsum.photos/seed/chair/300/300'],
            ['Mechanical Gaming Keyboard RGB', 'Electronics', 79.99, 4.8, 'Tactile mechanical switches with customizable RGB backlighting per key.', 'https://picsum.photos/seed/keyboard/300/300'],
            ['Stainless Steel Electric Kettle 1.7L', 'Kitchen', 34.95, 4.6, 'Fast boiling technology with auto shut-off and boil-dry protection.', 'https://picsum.photos/seed/kettle/300/300'],
            ['Ultra-Wide 34-Inch Curved Monitor', 'Electronics', 449.99, 4.9, '144Hz refresh rate with 1ms response time for gaming and productivity.', 'https://picsum.photos/seed/monitor/300/300'],
            ['Professional Chef Knife 8-Inch', 'Kitchen', 45.00, 4.7, 'High-carbon German steel blade with ergonomic non-slip handle.', 'https://picsum.photos/seed/knife/300/300'],
            ['Portable Bluetooth Speaker 20W', 'Electronics', 59.99, 4.4, 'Deep bass sound, IPX7 waterproof rating, perfect for outdoor use.', 'https://picsum.photos/seed/speaker/300/300']
        ];
        foreach ($sample_products as $p) {
            $seed_stmt->execute($p);
        }
    }
} catch (PDOException $e) {
    die("Database Connection Failure: " . $e->getMessage());
}

// Initialize Session Cart
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Router Action Controller
$page = $_GET['page'] ?? 'store';
$action = $_GET['action'] ?? '';

// ==========================================
// 2. BACKEND CONTROLLER LOGIC
// ==========================================

// Cart Add Action
if ($action === 'add_to_cart' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = (int)$_POST['product_id'];
    $qty = max(1, (int)($_POST['quantity'] ?? 1));
    $_SESSION['cart'][$product_id] = ($_SESSION['cart'][$product_id] ?? 0) + $qty;
    header("Location: index.php?page=cart");
    exit;
}

// Cart Update Action
if ($action === 'update_cart' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST['qty'] as $pid => $quantity) {
        $pid = (int)$pid;
        $quantity = (int)$quantity;
        if ($quantity <= 0) {
            unset($_SESSION['cart'][$pid]);
        } else {
            $_SESSION['cart'][$pid] = $quantity;
        }
    }
    header("Location: index.php?page=cart");
    exit;
}

// Cart Remove Single Item
if ($action === 'remove_item') {
    $pid = (int)$_GET['id'];
    unset($_SESSION['cart'][$pid]);
    header("Location: index.php?page=cart");
    exit;
}

// Submit Order Action
if ($action === 'place_order' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_SESSION['cart'])) {
        $name = trim($_POST['customer_name']);
        $email = trim($_POST['customer_email']);
        $address = trim($_POST['shipping_address']);

        // Calculate Totals
        $ids = implode(',', array_map('intval', array_keys($_SESSION['cart'])));
        $stmt = $db->query("SELECT * FROM products WHERE id IN ($ids)");
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $total_amount = 0;
        foreach ($products as $prod) {
            $qty = $_SESSION['cart'][$prod['id']];
            $total_amount += $prod['price'] * $qty;
        }

        // Insert Master Order
        $order_stmt = $db->prepare("INSERT INTO orders (customer_name, customer_email, shipping_address, total_amount) VALUES (?, ?, ?, ?)");
        $order_stmt->execute([$name, $email, $address, $total_amount]);
        $order_id = $db->lastInsertId();

        // Insert Order Line Items
        $item_stmt = $db->prepare("INSERT INTO order_items (order_id, product_name, price, quantity) VALUES (?, ?, ?, ?)");
        foreach ($products as $prod) {
            $qty = $_SESSION['cart'][$prod['id']];
            $item_stmt->execute([$order_id, $prod['name'], $prod['price'], $qty]);
        }

        // Flush Cart & Redirect to Confirmation
        $_SESSION['cart'] = [];
        header("Location: index.php?page=confirmation&order_id=" . $order_id);
        exit;
    }
}

// Admin Manager Actions
if ($action === 'update_status' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = (int)$_POST['order_id'];
    $status = $_POST['status'];
    $stmt = $db->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$status, $order_id]);
    header("Location: index.php?page=admin");
    exit;
}

if ($action === 'add_product' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $category = trim($_POST['category']);
    $price = (float)$_POST['price'];
    $description = trim($_POST['description']);
    $image_url = trim($_POST['image_url']) ?: 'https://via.placeholder.com/300';

    $stmt = $db->prepare("INSERT INTO products (name, category, price, description, image_url) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$name, $category, $price, $description, $image_url]);
    header("Location: index.php?page=admin");
    exit;
}

// Calculate total cart items for badge counter
$total_cart_items = array_sum($_SESSION['cart']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Amazon Clone Storefront & Dashboard</title>
    <style>
        * { box-sizing: border-box; font-family: Arial, sans-serif; margin: 0; padding: 0; }
        body { background-color: #e3e6e6; color: #0f1111; min-height: 100vh; display: flex; flex-direction: column; }

        /* Navigation Header */
        header { background-color: #131921; color: white; display: flex; align-items: center; justify-content: space-between; padding: 10px 20px; gap: 15px; flex-wrap: wrap; }
        .logo { font-size: 24px; font-weight: bold; color: #ff9900; text-decoration: none; display: flex; align-items: center; }
        .logo span { color: white; }
        .search-bar { flex: 1; max-width: 600px; display: flex; }
        .search-bar input { width: 100%; padding: 10px; border: none; border-radius: 4px 0 0 4px; outline: none; }
        .search-bar button { background-color: #febd69; border: none; padding: 10px 15px; border-radius: 0 4px 4px 0; cursor: pointer; font-weight: bold; }
        .search-bar button:hover { background-color: #f3a847; }
        .nav-links { display: flex; gap: 20px; align-items: center; }
        .nav-links a { color: white; text-decoration: none; font-size: 14px; }
        .nav-links a:hover { text-decoration: underline; }
        .cart-btn { background-color: #131921; border: 1px solid white; padding: 5px 10px; border-radius: 3px; font-weight: bold; position: relative; }
        .cart-badge { background-color: #f08804; color: black; border-radius: 50%; padding: 2px 7px; font-size: 12px; margin-left: 4px; }

        /* Secondary Sub-nav */
        .subnav { background-color: #232f3e; padding: 8px 20px; display: flex; gap: 15px; }
        .subnav a { color: white; text-decoration: none; font-size: 13px; font-weight: 500; }

        /* Layout Main Body */
        .main-container { flex: 1; padding: 20px; max-width: 1300px; margin: 0 auto; width: 100%; }

        /* Product Grid */
        .product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; }
        .card { background: white; padding: 20px; border-radius: 4px; border: 1px solid #ddd; display: flex; flex-direction: column; justify-content: space-between; }
        .card img { max-width: 100%; height: 200px; object-fit: contain; margin-bottom: 10px; }
        .card-title { font-size: 16px; font-weight: bold; text-decoration: none; color: #0f1111; margin-bottom: 5px; }
        .card-title:hover { color: #c7511f; }
        .price { font-size: 18px; color: #B12704; font-weight: bold; margin: 8px 0; }
        .rating { color: #ffa41c; font-size: 14px; margin-bottom: 10px; }
        .btn-yellow { background: #ffd814; border: 1px solid #fcd200; border-radius: 20px; padding: 8px 15px; cursor: pointer; font-weight: bold; width: 100%; text-align: center; text-decoration: none; display: inline-block; box-sizing: border-box; }
        .btn-yellow:hover { background: #f7ca00; }
        .btn-orange { background: #ffa41c; border: 1px solid #ff8f00; border-radius: 20px; padding: 8px 15px; cursor: pointer; font-weight: bold; width: 100%; color: black; text-decoration: none; display: inline-block; text-align: center; }

        /* Single Product Details */
        .product-detail { background: white; padding: 30px; border-radius: 4px; display: flex; gap: 30px; flex-wrap: wrap; }
        .product-detail img { max-width: 350px; width: 100%; object-fit: contain; }
        .product-info { flex: 1; min-width: 280px; }

        /* Tables & Cart Layout */
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 4px; overflow: hidden; margin-top: 15px; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #232f3e; color: white; }
        .cart-wrapper { display: flex; gap: 20px; flex-wrap: wrap; }
        .cart-table-container { flex: 3; min-width: 300px; }
        .checkout-box { flex: 1; min-width: 250px; background: white; padding: 20px; border-radius: 4px; border: 1px solid #ddd; height: fit-content; }

        /* Forms & Inputs */
        input[type="text"], input[type="email"], input[type="number"], textarea, select { width: 100%; padding: 8px; margin: 6px 0 15px; border: 1px solid #888; border-radius: 3px; }
        
        /* Dashboard Cards */
        .dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .dash-card { background: white; padding: 15px; border-radius: 4px; border-left: 5px solid #ff9900; }

        /* Status Pills */
        .status-pill { padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: bold; display: inline-block; }
        .status-Pending { background: #fff3cd; color: #856404; }
        .status-Processing { background: #cce5ff; color: #004085; }
        .status-Shipped { background: #d4edda; color: #155724; }
        .status-Completed { background: #d6d8d9; color: #1b1e21; }

        /* Footer */
        footer { background-color: #232f3e; color: white; text-align: center; padding: 20px; margin-top: auto; font-size: 13px; }
    </style>
</head>
<body>

<header>
    <a href="index.php?page=store" class="logo">amazon<span>.clone</span></a>
    <form class="search-bar" action="index.php" method="GET">
        <input type="hidden" name="page" value="store">
        <input type="text" name="search" placeholder="Search products..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
        <button type="submit">Search</button>
    </form>
    <div class="nav-links">
        <a href="index.php?page=admin"><strong>Manager Dashboard</strong></a>
        <a href="index.php?page=cart" class="cart-btn">
            🛒 Cart <span class="cart-badge"><?= $total_cart_items ?></span>
        </a>
    </div>
</header>

<div class="subnav">
    <a href="index.php?page=store">All Products</a>
    <a href="index.php?page=store&category=Electronics">Electronics</a>
    <a href="index.php?page=store&category=Kitchen">Kitchen</a>
    <a href="index.php?page=store&category=Home %26 Office">Home & Office</a>
</div>

<div class="main-container">

    <?php
    // ==========================================
    // VIEW 1: PRODUCT STOREFRONT & CATALOG
    // ==========================================
    if ($page === 'store'):
        $search = $_GET['search'] ?? '';
        $category = $_GET['category'] ?? '';

        $query = "SELECT * FROM products WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $query .= " AND (name LIKE ? OR description LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        if (!empty($category)) {
            $query .= " AND category = ?";
            $params[] = $category;
        }

        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>
        <h2 style="margin-bottom: 15px;">
            <?= $category ? htmlspecialchars($category) : ($search ? 'Search Results for "'.htmlspecialchars($search).'"' : 'Featured Products') ?>
        </h2>

        <?php if (empty($products)): ?>
            <p>No products found matching your search parameters.</p>
        <?php else: ?>
            <div class="product-grid">
                <?php foreach ($products as $prod): ?>
                    <div class="card">
                        <div>
                            <img src="<?= htmlspecialchars($prod['image_url']) ?>" alt="<?= htmlspecialchars($prod['name']) ?>">
                            <a href="index.php?page=product&id=<?= $prod['id'] ?>" class="card-title"><?= htmlspecialchars($prod['name']) ?></a>
                            <div class="rating">★ <?= $prod['rating'] ?> / 5.0</div>
                            <div class="price">$<?= number_format($prod['price'], 2) ?></div>
                        </div>
                        <form action="index.php?action=add_to_cart" method="POST">
                            <input type="hidden" name="product_id" value="<?= $prod['id'] ?>">
                            <button type="submit" class="btn-yellow">Add to Cart</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    <?php
    // ==========================================
    // VIEW 2: PRODUCT SINGLE DETAIL VIEW
    // ==========================================
    elseif ($page === 'product'):
        $pid = (int)($_GET['id'] ?? 0);
        $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$pid]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product):
            echo "<h2>Product not found.</h2>";
        else:
    ?>
        <div class="product-detail">
            <img src="<?= htmlspecialchars($product['image_url']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
            <div class="product-info">
                <h2><?= htmlspecialchars($product['name']) ?></h2>
                <p style="color: #565959; font-size: 14px; margin-top: 5px;">Category: <?= htmlspecialchars($product['category']) ?></p>
                <div class="rating" style="margin: 10px 0;">★ <?= $product['rating'] ?> Rating</div>
                <hr style="margin: 15px 0;">
                <div class="price" style="font-size: 24px;">$<?= number_format($product['price'], 2) ?></div>
                <p style="margin: 15px 0; line-height: 1.5;"><?= htmlspecialchars($product['description']) ?></p>
                
                <form action="index.php?action=add_to_cart" method="POST" style="max-width: 200px;">
                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                    <label>Quantity:</label>
                    <input type="number" name="quantity" value="1" min="1" max="10">
                    <button type="submit" class="btn-yellow">Add to Cart</button>
                </form>
            </div>
        </div>
    <?php 
        endif;

    // ==========================================
    // VIEW 3: SHOPPING CART & CHECKOUT
    // ==========================================
    elseif ($page === 'cart'):
        if (empty($_SESSION['cart'])):
    ?>
            <div style="background: white; padding: 40px; text-align: center; border-radius: 4px;">
                <h2>Your Amazon Cart is empty</h2>
                <p style="margin-top: 10px;"><a href="index.php?page=store" style="color: #007185;">Continue shopping</a></p>
            </div>
    <?php
        else:
            $ids = implode(',', array_map('intval', array_keys($_SESSION['cart'])));
            $stmt = $db->query("SELECT * FROM products WHERE id IN ($ids)");
            $cart_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $subtotal = 0;
    ?>
            <h2>Shopping Cart</h2>
            <div class="cart-wrapper" style="margin-top: 15px;">
                <div class="cart-table-container">
                    <form action="index.php?action=update_cart" method="POST">
                        <table>
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Total</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cart_products as $item): 
                                    $qty = $_SESSION['cart'][$item['id']];
                                    $line_total = $item['price'] * $qty;
                                    $subtotal += $line_total;
                                ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($item['name']) ?></strong>
                                        </td>
                                        <td>$<?= number_format($item['price'], 2) ?></td>
                                        <td>
                                            <input type="number" name="qty[<?= $item['id'] ?>]" value="<?= $qty ?>" min="0" style="width: 60px; margin: 0;">
                                        </td>
                                        <td>$<?= number_format($line_total, 2) ?></td>
                                        <td>
                                            <a href="index.php?action=remove_item&id=<?= $item['id'] ?>" style="color: #B12704; text-decoration: none;">Delete</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <button type="submit" class="btn-yellow" style="width: auto; margin-top: 15px;">Update Quantities</button>
                    </form>
                </div>

                <div class="checkout-box">
                    <h3>Subtotal (<?= $total_cart_items ?> items): <br><span style="color: #B12704;">$<?= number_format($subtotal, 2) ?></span></h3>
                    <hr style="margin: 15px 0;">
                    <h4>Shipping Address</h4>
                    <form action="index.php?action=place_order" method="POST">
                        <label>Full Name</label>
                        <input type="text" name="customer_name" required>

                        <label>Email Address</label>
                        <input type="email" name="customer_email" required>

                        <label>Street Address</label>
                        <textarea name="shipping_address" rows="3" required></textarea>

                        <button type="submit" class="btn-orange">Place Your Order</button>
                    </form>
                </div>
            </div>
    <?php 
        endif;

    // ==========================================
    // VIEW 4: ORDER CONFIRMATION PAGE
    // ==========================================
    elseif ($page === 'confirmation'):
        $order_id = (int)($_GET['order_id'] ?? 0);
        $stmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($order):
    ?>
            <div style="background: white; padding: 30px; border-radius: 4px; max-width: 600px; margin: 0 auto; border-top: 5px solid #2e7d32;">
                <h2 style="color: #2e7d32;">✓ Order Placed, Thank You!</h2>
                <p style="margin-top: 10px;">Order Number: <strong>#<?= $order['id'] ?></strong></p>
                <p>We sent a confirmation email to <strong><?= htmlspecialchars($order['customer_email']) ?></strong>.</p>
                <hr style="margin: 15px 0;">
                <h4>Shipping to:</h4>
                <p><?= htmlspecialchars($order['customer_name']) ?></p>
                <p><?= nl2br(htmlspecialchars($order['shipping_address'])) ?></p>
                <hr style="margin: 15px 0;">
                <p><strong>Total Amount Paid: $<?= number_format($order['total_amount'], 2) ?></strong></p>
                <br>
                <a href="index.php?page=store" class="btn-yellow">Continue Shopping</a>
            </div>
    <?php
        endif;

    // ==========================================
    // VIEW 5: MANAGER CONTROL DASHBOARD
    // ==========================================
    elseif ($page === 'admin'):
        // Metrics Queries
        $total_orders = $db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
        $total_revenue = $db->query("SELECT SUM(total_amount) FROM orders")->fetchColumn() ?: 0;
        $pending_orders = $db->query("SELECT COUNT(*) FROM orders WHERE status = 'Pending'")->fetchColumn();

        // Fetch Orders List
        $orders_stmt = $db->query("SELECT * FROM orders ORDER BY created_at DESC");
        $all_orders = $orders_stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>
        <h2>Manager Order Dashboard</h2>
        <br>

        <div class="dashboard-grid">
            <div class="dash-card">
                <h3>Total Orders</h3>
                <p style="font-size: 24px; font-weight: bold;"><?= $total_orders ?></p>
            </div>
            <div class="dash-card">
                <h3>Total Revenue</h3>
                <p style="font-size: 24px; font-weight: bold; color: #2e7d32;">$<?= number_format($total_revenue, 2) ?></p>
            </div>
            <div class="dash-card">
                <h3>Pending Fulfillments</h3>
                <p style="font-size: 24px; font-weight: bold; color: #b71c1c;"><?= $pending_orders ?></p>
            </div>
        </div>

        <div style="background: white; padding: 20px; border-radius: 4px; margin-bottom: 25px;">
            <h3>Add New Inventory Item</h3>
            <form action="index.php?action=add_product" method="POST" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; margin-top: 10px;">
                <div>
                    <label>Product Name</label>
                    <input type="text" name="name" required>
                </div>
                <div>
                    <label>Category</label>
                    <input type="text" name="category" required>
                </div>
                <div>
                    <label>Price ($)</label>
                    <input type="number" step="0.01" name="price" required>
                </div>
                <div>
                    <label>Image URL</label>
                    <input type="text" name="image_url" placeholder="https://...">
                </div>
                <div style="grid-column: 1 / -1;">
                    <label>Description</label>
                    <textarea name="description" rows="2" required></textarea>
                </div>
                <div style="grid-column: 1 / -1;">
                    <button type="submit" class="btn-yellow" style="width: auto;">Save Product to Catalog</button>
                </div>
            </form>
        </div>

        <h3>Customer Orders</h3>
        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer Info</th>
                    <th>Ordered Items</th>
                    <th>Total</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Update Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($all_orders as $ord): 
                    $item_stmt = $db->prepare("SELECT * FROM order_items WHERE order_id = ?");
                    $item_stmt->execute([$ord['id']]);
                    $items = $item_stmt->fetchAll(PDO::FETCH_ASSOC);
                ?>
                    <tr>
                        <td>#<?= $ord['id'] ?></td>
                        <td>
                            <strong><?= htmlspecialchars($ord['customer_name']) ?></strong><br>
                            <small><?= htmlspecialchars($ord['customer_email']) ?></small><br>
                            <small><?= htmlspecialchars($ord['shipping_address']) ?></small>
                        </td>
                        <td>
                            <ul style="padding-left: 15px; font-size: 13px;">
                                <?php foreach ($items as $it): ?>
                                    <li><?= htmlspecialchars($it['product_name']) ?> (x<?= $it['quantity'] ?>)</li>
                                <?php endforeach; ?>
                            </ul>
                        </td>
                        <td><strong>$<?= number_format($ord['total_amount'], 2) ?></strong></td>
                        <td><small><?= $ord['created_at'] ?></small></td>
                        <td>
                            <span class="status-pill status-<?= $ord['status'] ?>"><?= $ord['status'] ?></span>
                        </td>
                        <td>
                            <form action="index.php?action=update_status" method="POST" style="display: flex; gap: 5px;">
                                <input type="hidden" name="order_id" value="<?= $ord['id'] ?>">
                                <select name="status" style="margin: 0; padding: 4px;">
                                    <option value="Pending" <?= $ord['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="Processing" <?= $ord['status'] === 'Processing' ? 'selected' : '' ?>>Processing</option>
                                    <option value="Shipped" <?= $ord['status'] === 'Shipped' ? 'selected' : '' ?>>Shipped</option>
                                    <option value="Completed" <?= $ord['status'] === 'Completed' ? 'selected' : '' ?>>Completed</option>
                                </select>
                                <button type="submit" style="padding: 4px 8px; cursor: pointer;">Save</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    <?php endif; ?>

</div>

<footer>
    <p>&copy; <?= date('Y') ?> Amazon Clone Demo Platform. Powered by PHP & SQLite.</p>
</footer>

</body>
</html>