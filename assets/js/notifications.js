// assets/js/notifications.js

document.addEventListener('DOMContentLoaded', function() {
    initNotifications();
});

function initNotifications() {
    const bellBtn = document.getElementById('notifBellBtn') || document.getElementById('bellIconBtn');
    const dropdown = document.getElementById('notifDropdown') || document.getElementById('notificationDropdown');

    if (bellBtn && dropdown) {
        bellBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            dropdown.classList.toggle('hidden');
            if (!dropdown.classList.contains('hidden')) {
                loadNotifications();
            }
        });

        document.addEventListener('click', function(e) {
            if (!dropdown.contains(e.target) && e.target !== bellBtn) {
                dropdown.classList.add('hidden');
            }
        });
    }

    // Load initial unread count
    fetchUnreadCount();
}

function fetchUnreadCount() {
    fetch('backend/api/notifications/list.php?unread_only=true&limit=1')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const count = data.data.unread_count;
                const badge = document.getElementById('notifBadge') || document.getElementById('bellBadge');
                if (count > 0) {
                    badge.innerText = count > 9 ? '9+' : count;
                    badge.classList.remove('hidden');
                } else {
                    badge.classList.add('hidden');
                }
            }
        })
        .catch(err => console.error(err));
}

function loadNotifications() {
    const listContainer = document.getElementById('notifListContainer') || document.getElementById('notificationsList');
    if (!listContainer) return;
    listContainer.innerHTML = '<div class="p-4 text-center text-on-surface-variant">Loading notifications...</div>';

    fetch('backend/api/notifications/list.php')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const notes = data.data.notifications;
                const unreadCount = data.data.unread_count;

                // Update badge
                const badge = document.getElementById('notifBadge') || document.getElementById('bellBadge');
                if (unreadCount > 0) {
                    badge.innerText = unreadCount > 9 ? '9+' : unreadCount;
                    badge.classList.remove('hidden');
                } else {
                    badge.classList.add('hidden');
                }

                if (notes.length === 0) {
                    listContainer.innerHTML = '<div class="p-8 text-center text-on-surface-variant">No notifications.</div>';
                    return;
                }

                listContainer.innerHTML = notes.map(n => {
                    const isRead = parseInt(n.is_read) === 1;
                    const catColors = {
                        'Assignment': 'bg-blue-50 text-blue-700 border-blue-100',
                        'Reminder': 'bg-amber-50 text-amber-700 border-amber-100',
                        'Approval': 'bg-emerald-50 text-emerald-700 border-emerald-100',
                        'Escalation': 'bg-red-50 text-red-700 border-red-100'
                    };
                    const catColor = catColors[n.category] || 'bg-gray-50 text-gray-700 border-gray-100';

                    return `
                        <div class="p-md hover:bg-surface-container-low transition-colors border-b border-outline-variant flex gap-sm items-start relative ${!isRead ? 'bg-primary/5' : ''}">
                            <div class="flex-1 space-y-[2px]">
                                <div class="flex items-center gap-xs">
                                    <span class="inline-flex px-1.5 py-0.5 text-[9px] font-bold rounded border ${catColor}">
                                        ${n.category}
                                    </span>
                                    ${n.priority === 'Critical' || n.priority === 'High' ? `
                                        <span class="inline-flex px-1.5 py-0.5 text-[9px] font-bold rounded border bg-red-50 text-red-700 border-red-100">
                                            ${n.priority}
                                        </span>
                                    ` : ''}
                                </div>
                                <h4 class="font-semibold text-body-sm text-on-surface">${escapeHtml(n.title)}</h4>
                                <p class="text-caption text-on-surface-variant">${escapeHtml(n.message)}</p>
                            </div>
                            <div class="flex flex-col gap-xs items-end">
                                ${!isRead ? `
                                    <button onclick="markNotificationRead(event, ${n.id})" class="text-[11px] font-semibold text-primary hover:underline" title="Mark Read">
                                        Mark Read
                                    </button>
                                ` : ''}
                                <button onclick="deleteNotification(event, ${n.id})" class="text-on-surface-variant hover:text-red-600 transition-colors" title="Delete">
                                    <span class="material-symbols-outlined text-[16px]">delete</span>
                                </button>
                            </div>
                        </div>
                    `;
                }).join('');
            }
        })
        .catch(err => console.error(err));
}

function markNotificationRead(e, id) {
    if (e) e.stopPropagation();
    const formData = new FormData();
    formData.append('notification_id', id);

    fetch('backend/api/notifications/mark-read.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            loadNotifications();
        }
    })
    .catch(err => console.error(err));
}

function markAllNotificationsRead() {
    fetch('backend/api/notifications/mark-all-read.php', {
        method: 'POST'
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            loadNotifications();
        }
    })
    .catch(err => console.error(err));
}

function deleteNotification(e, id) {
    if (e) e.stopPropagation();
    const formData = new FormData();
    formData.append('notification_id', id);

    fetch('backend/api/notifications/delete.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            loadNotifications();
        }
    })
    .catch(err => console.error(err));
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}
