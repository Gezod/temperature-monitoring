<!-- Acknowledge Modal -->
<div class="modal fade" id="acknowledgeModal" tabindex="-1" data-bs-backdrop="false" data-bs-keyboard="false">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Acknowledge Anomaly</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="acknowledgeForm">
                    <div class="mb-3">
                        <label for="acknowledged_by" class="form-label">Acknowledged By</label>
                        <input type="text" class="form-control" id="acknowledged_by" required
                               placeholder="Enter your name or ID">
                        <div class="form-text">This will mark the anomaly as acknowledged and being investigated.</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" onclick="submitAcknowledge()">Acknowledge</button>
            </div>
        </div>
    </div>
</div>
