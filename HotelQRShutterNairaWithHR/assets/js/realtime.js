// assets/js/realtime.js
class RealTimeNotifier {
    constructor() {
        this.eventSource = null;
    }

    init() {
        this.connect();
        window.addEventListener('beforeunload', () => this.disconnect());
    }

    connect() {
        this.eventSource = new EventSource('notifications.php');
        
        this.eventSource.onmessage = (e) => {
            console.log("New update:", e.data);
            // Update your notification UI here
        };

        this.eventSource.onerror = () => {
            this.disconnect();
            setTimeout(() => this.connect(), 1000);
        };
    }

    disconnect() {
        if (this.eventSource) {
            this.eventSource.close();
        }
    }
}

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    new RealTimeNotifier().init();
});