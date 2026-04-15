<!-- Update the modal to use the correct path -->
<div id="logoutModal" class="modal">
    <div class="modal-content" style="max-width: 400px;">
        <div class="modal-header">
            <h2><i class="fas fa-sign-out-alt" style="color: #ef4444;"></i> Confirm Logout</h2>
            <span class="close-modal" onclick="closeModal('logoutModal')">&times;</span>
        </div>
        
        <div style="text-align: center; padding: 30px 20px;">
            <i class="fas fa-question-circle" style="font-size: 4rem; color: #f59e0b; margin-bottom: 20px;"></i>
            <p style="font-size: 1.1rem; margin-bottom: 30px; color: #333;">
                Are you sure you want to logout?
            </p>
            
            <div style="display: flex; gap: 15px; justify-content: center;">
                <a href="logout.php" class="btn-primary" style="background: #ef4444; padding: 12px 30px; text-decoration: none; display: inline-block;">
                    <i class="fas fa-check"></i> Yes, Logout
                </a>
                <button class="btn-secondary" onclick="closeModal('logoutModal')" style="padding: 12px 30px;">
                    <i class="fas fa-times"></i> Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Function to show logout confirmation
function confirmLogout() {
    document.getElementById('logoutModal').style.display = 'block';
}

// Close modal function
function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
}
</script>