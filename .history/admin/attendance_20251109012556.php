<div class="card shadow-sm mb-4">
  <div class="card-body">
    <form method="GET" action="attendance.php" class="row g-3 align-items-end">
      <div class="col-md-4">
        <label for="filter_date" class="form-label">เลือกวันที่:</label>
        <input type="date" class="form-control" id="filter_date" name="filter_date"
          value="<?php echo htmlspecialchars($filter_date); ?>">
      </div>
      <div class="col-md-4">
        <button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i> กรอง</button>
        <a href="attendance.php" class="btn btn-outline-secondary">วันนี้</a>
      </div>
    </form>
  </div>
</div>