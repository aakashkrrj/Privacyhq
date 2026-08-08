<?php
// governance/includes/dashboard-components.php
// Reusable Dashboard Layout Components

if (!function_exists('render_dashboard_card_start')) {
    /**
     * Render opening markup for a standard dashboard card layout component
     */
    function render_dashboard_card_start($title = '', $subtitle = '', $extraHeaderClass = '') {
        $html = '<div class="bg-surface-container-lowest p-5 md:p-6 rounded-xl shadow-[0px_2px_4px_rgba(0,0,0,0.04)] border border-[#EDEBE9] transition-all hover:shadow-md flex flex-col justify-between h-full group">';
        if (!empty($title) || !empty($subtitle)) {
            $html .= '<div class="flex flex-col sm:flex-row sm:items-center justify-between mb-4 gap-2 ' . htmlspecialchars($extraHeaderClass) . '">';
            $html .= '  <div>';
            if (!empty($title)) {
                $html .= '    <h3 class="font-title-md text-on-surface font-semibold text-base md:text-lg">' . htmlspecialchars($title) . '</h3>';
            }
            if (!empty($subtitle)) {
                $html .= '    <p class="font-caption text-outline text-xs mt-0.5">' . htmlspecialchars($subtitle) . '</p>';
            }
            $html .= '  </div>';
            $html .= '</div>';
        }
        return $html;
    }
}

if (!function_exists('render_dashboard_card_end')) {
    /**
     * Render closing markup for a standard dashboard card layout component
     */
    function render_dashboard_card_end() {
        return '</div>';
    }
}

if (!function_exists('render_metric_card')) {
    /**
     * Render a standardized summary metric card layout component
     */
    function render_metric_card($icon, $iconColorClass, $value, $label, $badgeHtml = '') {
        ?>
        <div class="bg-surface-container-lowest p-4 md:p-5 rounded-xl shadow-[0px_2px_4px_rgba(0,0,0,0.04)] border border-[#EDEBE9] flex flex-col justify-between h-full hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between mb-2">
                <span class="material-symbols-outlined <?= htmlspecialchars($iconColorClass) ?> text-2xl md:text-3xl" data-icon="<?= htmlspecialchars($icon) ?>"><?= htmlspecialchars($icon) ?></span>
                <?php if (!empty($badgeHtml)): ?>
                    <div class="flex items-center">
                        <?= $badgeHtml ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="mt-1">
                <div class="font-display text-2xl md:text-3xl font-bold text-on-surface tracking-tight"><?= htmlspecialchars($value) ?></div>
                <div class="font-caption text-outline text-xs md:text-sm font-medium mt-1 truncate"><?= htmlspecialchars($label) ?></div>
            </div>
        </div>
        <?php
    }
}
