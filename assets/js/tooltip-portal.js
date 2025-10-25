document.addEventListener('DOMContentLoaded', function() {
    const icons = document.querySelectorAll('.sql-info-icon, .inline-sql-info');

    icons.forEach(icon => {
        const tooltip = icon.querySelector('.sql-tooltip');
        if (!tooltip) return;

        let hideTimeout = null;

        function showTooltip() {
            clearTimeout(hideTimeout);

            // Move tooltip to body so it escapes stacking contexts
            if (!tooltip.classList.contains('portal-tooltip')) {
                tooltip.__originalParent = tooltip.parentElement;
                document.body.appendChild(tooltip);
                tooltip.classList.add('portal-tooltip');
            }

            // Prepare tooltip for measurement
            tooltip.style.visibility = 'hidden';
            tooltip.style.display = 'block';
            tooltip.style.opacity = '0';
            tooltip.style.pointerEvents = 'none';

            // Measure
            const tW = tooltip.offsetWidth;
            const tH = tooltip.offsetHeight;
            const iconRect = icon.getBoundingClientRect();
            const iconCenterX = iconRect.left + (iconRect.width / 2);

            const spacing = 8; // gap between icon and tooltip (smaller for compact layout)
            // Try to place below icon, else above
            let top = iconRect.bottom + spacing;
            if (top + tH > window.innerHeight - 10) {
                top = iconRect.top - tH - spacing;
                if (top < 10) top = 10;
            }

            // Center tooltip horizontally on icon by default
            let left = iconCenterX - (tW / 2);
            // Clamp within viewport
            if (left < 10) left = 10;
            if (left + tW > window.innerWidth - 10) left = window.innerWidth - tW - 10;

            // Arrow position relative to tooltip left edge
            let arrowLeft = iconCenterX - left;
            // Constrain arrow inside tooltip bounds with some padding
            arrowLeft = Math.max(12, Math.min(tW - 12, arrowLeft));

            // Apply position and arrow
            tooltip.style.left = Math.round(left) + 'px';
            tooltip.style.top = Math.round(top) + 'px';
            tooltip.style.setProperty('--arrow-left', Math.round(arrowLeft) + 'px');

            // Show tooltip
            tooltip.style.visibility = 'visible';
            tooltip.style.opacity = '1';
            tooltip.style.pointerEvents = 'auto';
        }

        function hideTooltip() {
            clearTimeout(hideTimeout);
            hideTimeout = setTimeout(() => {
                // Hide
                tooltip.style.visibility = 'hidden';
                tooltip.style.opacity = '0';
                tooltip.style.pointerEvents = 'none';
                // Restore into original parent to keep DOM tidy
                if (tooltip.__originalParent) {
                    tooltip.__originalParent.appendChild(tooltip);
                    tooltip.classList.remove('portal-tooltip');
                }
            }, 180);
        }

        icon.addEventListener('mouseenter', showTooltip);
        icon.addEventListener('mouseleave', hideTooltip);

        // Keep visible while hovering the tooltip itself
        tooltip.addEventListener('mouseenter', function() {
            clearTimeout(hideTimeout);
            // Recalculate in case layout shifted
            showTooltip();
        });
        tooltip.addEventListener('mouseleave', hideTooltip);

        // Reposition on scroll/resize when visible
        window.addEventListener('scroll', function() {
            if (tooltip.style.visibility === 'visible') showTooltip();
        }, { passive: true });
        window.addEventListener('resize', function() {
            if (tooltip.style.visibility === 'visible') showTooltip();
        });
    });
});