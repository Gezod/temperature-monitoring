<!-- Resolve Modal -->
<div class="modal fade" id="resolveModal" tabindex="-1" data-bs-backdrop="false" data-bs-keyboard="false">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Resolve Anomaly</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="resolveForm">
                    <div class="mb-3">
                        <label for="resolution_notes" class="form-label">Resolution Notes</label>
                        <textarea class="form-control" id="resolution_notes" rows="4" required
                                  placeholder="Describe how the anomaly was resolved, actions taken, and any follow-up needed..."></textarea>
                        <div class="form-text">Please provide detailed information about the resolution for future reference.</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" onclick="submitResolve()">Mark as Resolved</button>
            </div>
        </div>
    </div>
</div>
