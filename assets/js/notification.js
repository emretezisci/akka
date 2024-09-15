document.addEventListener('DOMContentLoaded', function() {
    // Function to create a notification
    window.createNotification = function(title, body, type, imageUrl = null) {
        const container = document.querySelector('.notification-container');
        console.log('Notification Triggered!');

        // Create the image HTML only if imageUrl is provided
        let imageHtml = '';
        if (imageUrl !== null && imageUrl !== '') {
            imageHtml = `<img src="${imageUrl}" alt="Icon" class="notification-icon">`;
        }

        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification ${type} hidden`; 
        notification.innerHTML = `
            <div class="innernoti">
                ${imageHtml} 
                <div class="text-content">
                    <div class="notification-header">
                        <span class="notification-title">${title}</span>
                        <button class="close-btn">×</button>
                    </div>
                    <div class="notification-body">${body}</div>
                </div>
            </div>
        `;

        // Append the notification to the container
        container.appendChild(notification);

        // Show notification with a delay to allow CSS transition
        setTimeout(() => {
            notification.classList.remove('hidden');
        }, 100);

        // Set auto-hide with cleanup
        setTimeout(() => {
            notification.classList.add('hidden');
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 500); // Ensure smooth fading before removal
        }, 5000); // Adjust auto-hide duration as needed

        // Close button functionality
        notification.querySelector('.close-btn').addEventListener('click', () => {
            notification.classList.add('hidden');
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 500); // Remove from DOM after transition
        });
    };
});