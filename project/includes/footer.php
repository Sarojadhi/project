<?php
/**
 * Shared Footer
 * 
 * Closes the HTML document. Included at the bottom of every page.
 * 
 * Usage (at the end of a page):
 *   <script src="/assets/js/validate-milk-entry.js"></script>
 *   <?php include __DIR__ . '/../includes/footer.php'; ?>
 * 
 * Any page-specific scripts should be placed BEFORE this include,
 * so they load after the page content but before </body>.
 */
?>

<footer class="bg-white border-t border-gray-200 mt-10">
    <div class="container mx-auto px-4 py-4 text-center">
        <p class="text-sm text-gray-500">
            Shree Tri Shakti Dairy &mdash; Milk Collection Management System
        </p>
        <p class="text-xs text-gray-400 mt-1">
            BCA 4th Semester Project &copy; <?php echo date('Y'); ?>
        </p>
    </div>
</footer>

</body>
</html>