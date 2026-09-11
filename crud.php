<!-- Verificaion -->
<div class="col-12">
                            <label for="Email" class="form-label small fw-bold text-uppercase text-muted"
                                style="letter-spacing: 0.5px;"> </label>
                            <input type="text" id="Email" name="Email" class="form-control"
                                placeholder="Enter Email" style="text-transform: lowercase;"
                                value="<?= htmlspecialchars($record['Email'] ?? '') ?>" required>
                        </div>