document.getElementById('createDelivery').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('process_delivery.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            // Show success message with tracking ID
            const alert = `
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    Delivery scheduled! Your tracking ID is: <strong>${data.tracking_id}</strong>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            this.insertAdjacentHTML('beforebegin', alert);
            this.reset();
        }
    });
});
