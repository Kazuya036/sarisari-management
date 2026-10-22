<?php
require_once __DIR__ . '/includes/bootstrap.php'; require_login();
$pageTitle='Products & Inventory';
$isAdmin = ($_SESSION['role'] ?? '') === 'admin';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    verify_csrf();
    $action=$_POST['action']??'';
    try {
        if ($action==='save') {
            $id=(int)($_POST['id']??0); $name=trim((string)($_POST['name']??'')); $sku=trim((string)($_POST['sku']??'')); $category=(int)($_POST['category_id']??0) ?: null; $supplier=(int)($_POST['supplier_id']??0) ?: null;
            $unit=trim((string)($_POST['unit']??'piece')); $cost=(float)($_POST['cost_price']??0); $price=(float)($_POST['selling_price']??0); $stock=(float)($_POST['stock_qty']??0); $min=(float)($_POST['min_stock']??5); $expiry=trim((string)($_POST['expiration_date']??'')) ?: null;
            if($name===''||$cost<0||$price<0||$stock<0||$min<0) throw new RuntimeException('Enter a product name and valid non-negative amounts.');
            if($id>0){
                $pdo->beginTransaction();
                $oldQ=$pdo->prepare('SELECT stock_qty FROM products WHERE id=? FOR UPDATE');$oldQ->execute([$id]);$old=$oldQ->fetch();if(!$old)throw new RuntimeException('Product not found.');
                $stmt=$pdo->prepare('UPDATE products SET category_id=?,supplier_id=?,name=?,sku=?,unit=?,cost_price=?,selling_price=?,stock_qty=?,min_stock=?,expiration_date=? WHERE id=?');
                $stmt->execute([$category,$supplier,$name,$sku?:null,$unit,$cost,$price,$stock,$min,$expiry,$id]);
                $diff=$stock-(float)$old['stock_qty']; if(abs($diff)>0.0001){$m=$pdo->prepare("INSERT INTO inventory_movements(product_id,user_id,movement_type,quantity_change,notes) VALUES(?,?,'adjustment',?,?)");$m->execute([$id,$_SESSION['user_id'],$diff,'Stock updated through product form']);}
                $pdo->commit(); log_action($pdo,'update','product',$id,$name); flash('success','Product updated successfully.');
            } else {
                $pdo->beginTransaction();
                $stmt=$pdo->prepare('INSERT INTO products(category_id,supplier_id,name,sku,unit,cost_price,selling_price,stock_qty,min_stock,expiration_date) VALUES(?,?,?,?,?,?,?,?,?,?)');
                $stmt->execute([$category,$supplier,$name,$sku?:null,$unit,$cost,$price,$stock,$min,$expiry]);$newId=(int)$pdo->lastInsertId();
                if($stock>0){$m=$pdo->prepare("INSERT INTO inventory_movements(product_id,user_id,movement_type,quantity_change,notes) VALUES(?,?,'opening',?,'Opening stock')");$m->execute([$newId,$_SESSION['user_id'],$stock]);}
                $pdo->commit();log_action($pdo,'create','product',$newId,$name);flash('success','Product added successfully.');
            }
        } elseif($action==='archive') {
            $id=(int)($_POST['id']??0);if(!$isAdmin)throw new RuntimeException('Only the store owner can archive products.');
            $stmt=$pdo->prepare('UPDATE products SET active=0 WHERE id=?');$stmt->execute([$id]);log_action($pdo,'archive','product',$id);flash('success','Product archived.');
        } elseif($action==='adjust') {
            $id=(int)($_POST['id']??0);$change=(float)($_POST['quantity_change']??0);$reason=trim((string)($_POST['notes']??'Manual adjustment'));
            if($change==0)throw new RuntimeException('Adjustment quantity cannot be zero.');
            $pdo->beginTransaction();$q=$pdo->prepare('SELECT stock_qty FROM products WHERE id=? AND active=1 FOR UPDATE');$q->execute([$id]);$p=$q->fetch();if(!$p)throw new RuntimeException('Product not found.');
            if((float)$p['stock_qty']+$change<0)throw new RuntimeException('Stock cannot become negative.');
            $pdo->prepare('UPDATE products SET stock_qty=stock_qty+? WHERE id=?')->execute([$change,$id]);
            $pdo->prepare("INSERT INTO inventory_movements(product_id,user_id,movement_type,quantity_change,notes) VALUES(?,?,'adjustment',?,?)")->execute([$id,$_SESSION['user_id'],$change,$reason]);
            $pdo->commit();log_action($pdo,'adjust_stock','product',$id,$reason);flash('success','Inventory adjusted.');
        }
    } catch(Throwable $ex){if($pdo->inTransaction())$pdo->rollBack();flash('error',$ex instanceof PDOException?'Could not save. Check for a duplicate SKU or invalid value.':$ex->getMessage());}
    header('Location: products.php');exit;
}
$categories=$pdo->query('SELECT id,name FROM categories ORDER BY name')->fetchAll();$suppliers=$pdo->query('SELECT id,name FROM suppliers ORDER BY name')->fetchAll();
$search=trim((string)($_GET['q']??''));$stockFilter=$_GET['stock']??'';$sql="SELECT p.*,c.name category_name,s.name supplier_name FROM products p LEFT JOIN categories c ON c.id=p.category_id LEFT JOIN suppliers s ON s.id=p.supplier_id WHERE p.active=1";$args=[];
if($search!==''){$sql.=' AND (p.name LIKE ? OR p.sku LIKE ?)';$args[]="%$search%";$args[]="%$search%";}
if($stockFilter==='low')$sql.=' AND p.stock_qty <= p.min_stock';$sql.=' ORDER BY p.name LIMIT 500';$stmt=$pdo->prepare($sql);$stmt->execute($args);$products=$stmt->fetchAll();
$edit=null;if(isset($_GET['edit'])){$q=$pdo->prepare('SELECT * FROM products WHERE id=? AND active=1');$q->execute([(int)$_GET['edit']]);$edit=$q->fetch()?:null;}
require __DIR__.'/includes/header.php';
?>
<div class="page-toolbar"><div><p class="muted no-margin">Manage your product catalog and stock levels.</p></div><button class="btn primary" type="button" onclick="document.getElementById('productForm').scrollIntoView({behavior:'smooth'});">＋ Add product</button></div>
<div class="stats-grid compact-stats"><article class="stat-card"><span class="muted">Products shown</span><strong><?= count($products) ?></strong></article><article class="stat-card"><span class="muted">Low stock</span><strong><?= (int)$pdo->query('SELECT COUNT(*) FROM products WHERE active=1 AND stock_qty<=min_stock')->fetchColumn() ?></strong></article><article class="stat-card"><span class="muted">Out of stock</span><strong><?= (int)$pdo->query('SELECT COUNT(*) FROM products WHERE active=1 AND stock_qty<=0')->fetchColumn() ?></strong></article></div>
<div class="panel"><div class="panel-heading"><div><h3>Product catalog</h3><p>Search, review, and maintain your products.</p></div></div><form class="filter-bar" method="get"><input name="q" value="<?= e($search) ?>" placeholder="Search product name or SKU"><select name="stock"><option value="">All stock levels</option><option value="low" <?= $stockFilter==='low'?'selected':'' ?>>Low stock only</option></select><button class="btn secondary" type="submit">Search</button><a class="btn ghost" href="products.php">Reset</a></form>
<div class="table-wrap"><table><thead><tr><th>Product</th><th>Category</th><th>Cost</th><th>Selling price</th><th>Stock</th><th>Status</th><th>Actions</th></tr></thead><tbody>
<?php foreach($products as $p): $low=(float)$p['stock_qty'] <= (float)$p['min_stock']; ?><tr><td><strong><?= e($p['name']) ?></strong><small class="table-sub"><?= e($p['sku']??'No SKU') ?> · <?= e($p['unit']) ?></small></td><td><?= e($p['category_name']??'Uncategorized') ?></td><td><?= money($p['cost_price']) ?></td><td><strong><?= money($p['selling_price']) ?></strong></td><td><?= e((string)$p['stock_qty']) ?> <small><?= e($p['unit']) ?></small></td><td><span class="badge <?= (float)$p['stock_qty']<=0?'danger':($low?'warning':'success') ?>"><?= (float)$p['stock_qty']<=0?'Out of stock':($low?'Low stock':'In stock') ?></span></td><td><div class="action-group"><a class="btn small secondary" href="products.php?edit=<?= (int)$p['id'] ?>#productForm">Edit</a><button class="btn small ghost" type="button" onclick="document.getElementById('adjust-<?= (int)$p['id'] ?>').showModal()">Adjust</button><?php if($isAdmin): ?><form method="post" onsubmit="return confirm('Archive this product? It will no longer appear in active inventory.')" class="inline-form"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="archive"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><button class="btn small danger-outline" type="submit">Archive</button></form><?php endif; ?></div><dialog id="adjust-<?= (int)$p['id'] ?>" class="modal"><form method="post" class="modal-form"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="adjust"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><h3>Adjust stock</h3><p class="muted">Current stock: <?= e((string)$p['stock_qty']) ?> <?= e($p['unit']) ?>. Use a negative value to reduce stock.</p><label>Quantity change</label><input type="number" step="0.001" name="quantity_change" required><label>Reason</label><input name="notes" maxlength="255" required placeholder="e.g. Damaged items"><div class="modal-actions"><button class="btn secondary" type="button" onclick="this.closest('dialog').close()">Cancel</button><button class="btn primary">Save adjustment</button></div></form></dialog></td></tr><?php endforeach; ?>
<?php if(!$products): ?><tr><td colspan="7"><div class="empty-state compact"><strong>No products found</strong><p>Add a product or change your search.</p></div></td></tr><?php endif; ?>
</tbody></table></div></div>
<div class="panel form-panel" id="productForm"><div class="panel-heading"><div><h3><?= $edit?'Edit product':'Add a product' ?></h3><p>Keep pricing and inventory information accurate.</p></div><?php if($edit): ?><a class="btn ghost" href="products.php#productForm">Cancel edit</a><?php endif; ?></div>
<form method="post" class="form-grid"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int)($edit['id']??0) ?>">
<div class="field"><label>Product name *</label><input name="name" required maxlength="160" value="<?= e($edit['name']??'') ?>" placeholder="e.g. Instant noodles"></div>
<div class="field"><label>SKU / Barcode</label><input name="sku" maxlength="80" value="<?= e($edit['sku']??'') ?>" placeholder="Optional unique code"></div>
<div class="field"><label>Category</label><select name="category_id"><option value="">Choose category</option><?php foreach($categories as $c): ?><option value="<?= $c['id'] ?>" <?= (string)($edit['category_id']??'')===(string)$c['id']?'selected':'' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select></div>
<div class="field"><label>Supplier</label><select name="supplier_id"><option value="">Choose supplier</option><?php foreach($suppliers as $s): ?><option value="<?= $s['id'] ?>" <?= (string)($edit['supplier_id']??'')===(string)$s['id']?'selected':'' ?>><?= e($s['name']) ?></option><?php endforeach; ?></select></div>
<div class="field"><label>Unit</label><select name="unit"><?php foreach(['piece','pack','bottle','can','sachet','box','kg','gram','liter','bundle'] as $u): ?><option value="<?= $u ?>" <?= ($edit['unit']??'piece')===$u?'selected':'' ?>><?= ucfirst($u) ?></option><?php endforeach; ?></select></div>
<div class="field"><label>Cost price (₱) *</label><input type="number" name="cost_price" min="0" step="0.01" required value="<?= e((string)($edit['cost_price']??'0.00')) ?>"></div>
<div class="field"><label>Selling price (₱) *</label><input type="number" name="selling_price" min="0" step="0.01" required value="<?= e((string)($edit['selling_price']??'0.00')) ?>"></div>
<div class="field"><label>Current stock *</label><input type="number" name="stock_qty" min="0" step="0.001" required value="<?= e((string)($edit['stock_qty']??'0')) ?>"></div>
<div class="field"><label>Minimum stock alert</label><input type="number" name="min_stock" min="0" step="0.001" value="<?= e((string)($edit['min_stock']??'5')) ?>"></div>
<div class="field"><label>Expiration date (optional)</label><input type="date" name="expiration_date" value="<?= e($edit['expiration_date']??'') ?>"></div>
<div class="field field-action"><button class="btn primary" type="submit"><?= $edit?'Save changes':'Add product' ?></button></div>
</form><p class="form-help">For an existing product, changing the current stock also creates an inventory adjustment record.</p></div>
<?php require __DIR__.'/includes/footer.php'; ?>
