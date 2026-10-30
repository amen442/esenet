<?php
// Include required files
require_once __DIR__ . '/../../config/db_connect.php';
require_once __DIR__ . '/../layouts/components.php';

// Récupérer les notifications
$teacher_id = get_teacher_id();
if ($teacher_id) {
    $notifications = get_notifications($teacher_id, 'ENSEIGNANT', 10);
    $unread_count = get_unread_notifications_count($teacher_id);
} else {
    $notifications = [];
    $unread_count = 0;
}
?>

<!-- Notification Box (like chatbot) -->
<div class="notification-box" id="notificationBox">
    <div class="notification-header">
        <div class="notification-title">
            <i class="fas fa-bell"></i>
            <span>Notifications</span>
            <?php if ($unread_count > 0): ?>
                <span class="notification-badge"><?php echo $unread_count; ?></span>
            <?php endif; ?>
        </div>
        <div class="notification-controls">
            <button class="notification-btn" onclick="markAllAsRead()" title="Tout marquer comme lu">
                <i class="fas fa-check-double"></i>
            </button>
            <button class="notification-btn close-btn" onclick="toggleNotifications()" title="Fermer">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
    <div class="notification-body">
        <?php if (empty($notifications)): ?>
            <div class="notification-empty">
                <i class="fas fa-bell-slash"></i>
                <p>Aucune notification</p>
            </div>
        <?php else: ?>
            <?php foreach ($notifications as $notif): ?>
                <div class="notification-item <?php echo $notif['lu'] ? 'read' : 'unread'; ?>" data-id="<?php echo $notif['id']; ?>">
                    <div class="notification-icon">
                        <?php if ($notif['type_notification'] === 'EVENEMENT'): ?>
                            <i class="fas fa-calendar-alt"></i>
                        <?php elseif ($notif['type_notification'] === 'OFFRE'): ?>
                            <i class="fas fa-briefcase"></i>
                        <?php elseif ($notif['type_notification'] === 'CANDIDATURE'): ?>
                            <i class="fas fa-user-check"></i>
                        <?php elseif ($notif['type_notification'] === 'RECOMMANDATION'): ?>
                            <i class="fas fa-star"></i>
                        <?php else: ?>
                            <i class="fas fa-info-circle"></i>
                        <?php endif; ?>
                    </div>
                    <div class="notification-content">
                        <h4><?php echo htmlspecialchars($notif['titre']); ?></h4>
                        <p><?php echo htmlspecialchars($notif['message']); ?></p>
                        <span class="notification-time"><?php echo date('d/m/Y H:i', strtotime($notif['date_creation'])); ?></span>
                    </div>
                    <?php if (!$notif['lu']): ?>
                        <div class="notification-dot"></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Notification Toggle Button -->
<button class="notification-toggle" onclick="toggleNotifications()" id="notificationToggle">
    <i class="fas fa-bell"></i>
    <?php if ($unread_count > 0): ?>
        <span class="notification-badge"><?php echo $unread_count; ?></span>
    <?php endif; ?>
</button>

<script>
function toggleNotifications() {
    const box = document.getElementById('notificationBox');
    box.classList.toggle('open');
}

function markAllAsRead() {
    fetch('mark_notifications_read.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=mark_all'
    }).then(() => {
        document.querySelectorAll('.notification-item').forEach(item => {
            item.classList.remove('unread');
            item.classList.add('read');
            const dot = item.querySelector('.notification-dot');
            if (dot) dot.remove();
        });
        const badges = document.querySelectorAll('.notification-badge');
        badges.forEach(badge => badge.remove());
    });
}

// Marquer comme lu au clic
document.querySelectorAll('.notification-item').forEach(item => {
    item.addEventListener('click', function() {
        const id = this.dataset.id;
        fetch('mark_notifications_read.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=mark_one&id=' + id
        }).then(() => {
            this.classList.remove('unread');
            this.classList.add('read');
            const dot = this.querySelector('.notification-dot');
            if (dot) dot.remove();
        });
    });
});
</script>
