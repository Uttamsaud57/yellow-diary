<?php

include 'db.php';
include 'includes/header.php';
include 'includes/sidebar.php';

// Filter logic
$filter_start = $_GET['start_date'] ?? '';
$filter_end = $_GET['end_date'] ?? '';
$view = 'all'; // Force view to show all businesses

$where = [];
$params = [];

if ($filter_start && $filter_end) {
    $where[] = "DATE(created_at) BETWEEN ? AND ?";
    $params[] = $filter_start;
    $params[] = $filter_end;
}

$where_clause = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Pagination setup
$per_page = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $per_page;

$sql = "SELECT * FROM businesses $where_clause ORDER BY created_at DESC LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;
$types = str_repeat('s', count($params) - 2) . 'ii';

$stmt = $conn->prepare($sql);
if (!empty($params)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$businesses = $stmt->get_result();

$total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM businesses"));

$count_sql = "SELECT COUNT(*) AS total FROM businesses $where_clause";
$count_stmt = $conn->prepare($count_sql);
if (!empty($where)) {
    $bind_types = str_repeat('s', count($params) - 2);
    $count_stmt->bind_param($bind_types, ...array_slice($params, 0, -2));
}
$count_stmt->execute();
$result = $count_stmt->get_result();
$count_data = $result->fetch_assoc();
$total_pages = ceil($count_data['total'] / $per_page);
?>

<div class="content-wrapper">
  <section class="content-header">
    <h1 class="mb-3">Business Reports</h1>
  </section>

  <section class="content">
    <!-- Filters -->
    <form class="row mb-4" method="GET">
      <div class="col-md-3">
        <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($filter_start) ?>">
      </div>
      <div class="col-md-3">
        <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($filter_end) ?>">
      </div>
      <div class="col-md-3 mt-2">
        <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Apply</button>
        <a href="business-reports.php" class="btn btn-secondary"><i class="fas fa-sync"></i></a>
      </div>
    </form>

    <!-- Summary Cards -->
    <div class="row mb-3">
      <div class="col-lg-4 col-6">
        <div class="small-box bg-info">
          <div class="inner">
            <h3><?= $total['total'] ?></h3>
            <p>Total Businesses</p>
          </div>
          <div class="icon"><i class="fas fa-building"></i></div>
        </div>
      </div>
    </div>

    <!-- All Business Listings -->
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">All Businesses</h3>
        <a href="export_businesses.php" class="btn btn-sm btn-success"><i class="fas fa-file-excel"></i> Export Excel</a>
      </div>
      <div class="card-body table-responsive">
        <table class="table table-striped table-bordered">
          <thead>
            <tr>
              <th>ID</th>
              <th>Name</th>
              <th>Category</th>
              <th>Location</th>
              <th>Description</th>
              <th>Image</th>
              <th>URL</th>
              <th>Created At</th>
            </tr>
          </thead>
          <tbody>
            <?php while($row = mysqli_fetch_assoc($businesses)): ?>
              <tr>
                <td><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><span class="badge bg-primary"><?= htmlspecialchars($row['category']) ?></span></td>
                <td><?= htmlspecialchars($row['location']) ?></td>
                <td><?= substr(strip_tags($row['description']), 0, 80) ?>...</td>
                <td>
                  <?php if (!empty($row['image'])): ?>
                    <img src="<?= htmlspecialchars($row['image']) ?>" class="img-thumbnail" style="width: 60px; height: 60px;">
                  <?php else: ?>
                    <span class="text-muted">N/A</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($row['url']): ?>
                    <a href="<?= htmlspecialchars($row['url']) ?>" target="_blank">Visit</a>
                  <?php else: ?>
                    <span class="text-muted">N/A</span>
                  <?php endif; ?>
                </td>
                <td><?= date('Y-m-d', strtotime($row['created_at'])) ?></td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>

        <?php if ($total_pages > 1): ?>
          <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center mt-4">
              <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                  <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
                    <?= $i ?>
                  </a>
                </li>
              <?php endfor; ?>
            </ul>
          </nav>
        <?php endif; ?>
      </div>
    </div>
  </section>
</div>

<?php include 'includes/footer.php'; ?>
