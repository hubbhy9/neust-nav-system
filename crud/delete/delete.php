<form method="POST" action="functions/delete.php">
    <!-- Hidden ID for the backend -->
    <input type="hidden" name="user_id" value="<?= htmlspecialchars($row['user_id'] ?? '') ?>">

    <!-- Modal Header -->
    <div class="modal-header border-0 py-3" style="background:#F6F7FA; border-top:4px solid #FFCE00; border-radius: calc(0.5rem - 1px) calc(0.5rem - 1px) 0 0;">
        <h5 class="modal-title fw-bold" style="color:#15171F;">
            <i class="bi bi-trash3 text-danger me-2"></i>Confirm Deletion
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <!-- Modal Body -->
    <div class="modal-body p-4">
        <div class="text-center mb-4">
            <div class="display-6 text-danger mb-2">
                <i class="bi bi-exclamation-octagon"></i>
            </div>
            <h6 class="fw-bold text-dark">Are you sure you want to delete this account?</h6>
            <p class="text-muted small">This action is permanent and cannot be undone.</p>
        </div>

        <!-- User Summary Card (Read Only) -->
        <div class="bg-light p-3 rounded-3 border">
            <div class="mb-2">
                <label class="form-label smallest fw-bold text-uppercase text-muted mb-0" style="font-size: 0.7rem;">Target User</label>
                <div class="fw-bold text-dark">
                    <?= htmlspecialchars(($row['last_name'] ?? '') . ", " . ($row['first_name'] ?? '') . " " . ($row['suffix'] ?? '')); ?>
                </div>
            </div>
            <div class="row">
                <div class="col-4">
                    <label class="form-label smallest fw-bold text-uppercase text-muted mb-0" style="font-size: 0.7rem;">User ID</label>
                    <div class="small text-dark">#<?= htmlspecialchars($row['user_id'] ?? '') ?></div>
                </div>
                <div class="col-8">
                    <label class="form-label smallest fw-bold text-uppercase text-muted mb-0" style="font-size: 0.7rem;">Email Address</label>
                    <div class="small text-dark text-truncate"><?= htmlspecialchars($row['email'] ?? '') ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Footer -->
    <div class="modal-footer border-0 pt-0 pb-4 px-4">
        <div class="row g-2 w-100">
            <div class="col-6">
                <button type="button" class="btn btn-light border w-100 py-2 fw-bold" data-bs-dismiss="modal">Cancel</button>
            </div>
            <div class="col-6">
                <!-- Changed to btn-danger to indicate a destructive action -->
                <button type="submit" name="delete" class="btn btn-danger w-100 py-2 fw-bold shadow-sm">
                    Delete User <i class="bi bi-trash ms-1"></i>
                </button>
            </div>
        </div>
    </div>
</form>