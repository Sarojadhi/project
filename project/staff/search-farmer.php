<?php
/**
 * Staff - Search Farmer
 * 
 * Allows staff to look up a farmer by:
 *   - Farmer ID (exact match, if the search text is numeric)
 *   - Username or full name (partial text match)
 * 
 * After a single match is found, staff can:
 *   - See the farmer's profile
 *   - Jump directly to enter milk or dana for that farmer
 * 
 * Access: Only staff. Enforced by requireRole('staff').
 * 
 * Note: No rupee amounts are shown here — staff cannot see financial data.
 */

require_once __DIR__ . '/../includes/auth.php';
requireRole('staff');

// =====================================================
// 1. READ SEARCH TEXT FROM URL
// =====================================================
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$results = null;
$farmer = null;

// =====================================================
// 2. RUN THE SEARCH (if the user typed something)
// =====================================================
if ($search != '') {

    if (is_numeric($search)) {
        // ----- Search by farmer ID (exact match) -----
        $id = (int)$search;
        $sql = "SELECT f.id, f.address, f.join_date, f.status,
                       u.username, u.full_name, u.phone
                FROM farmers f
                JOIN users u ON f.user_id = u.id
                WHERE f.id = $id";
    } else {
        // ----- Search by username or name (partial match) -----
        // Escape the search text to prevent SQL injection
        $safe = $conn->real_escape_string($search);
        $sql = "SELECT f.id, f.address, f.join_date, f.status,
                       u.username, u.full_name, u.phone
                FROM farmers f
                JOIN users u ON f.user_id = u.id
                WHERE u.username LIKE '%$safe%'
                   OR u.full_name LIKE '%$safe%'
                ORDER BY u.full_name ASC
                LIMIT 20";
    }

    $results = $conn->query($sql);

    // If exactly one match, pull out the full row for the profile view
    if ($results->num_rows == 1) {
        $farmer = $results->fetch_assoc();
    }
}

// =====================================================
// 3. IF A SINGLE FARMER WAS FOUND, LOAD QUICK STATS
// =====================================================
$milkCount = 0;
$danaCount = 0;

if ($farmer != null) {

    $fid = $farmer['id'];

    // Milk entry count
    $sql = "SELECT COUNT(*) AS total FROM milk_entries WHERE farmer_id = $fid";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    $milkCount = $row['total'];

    // Dana entry count
    $sql = "SELECT COUNT(*) AS total FROM dana_entries WHERE farmer_id = $fid";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    $danaCount = $row['total'];
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container mx-auto px-4 py-6">

    <h1 class="text-3xl font-bold text-gray-800 mb-6">Search Farmer</h1>

    <!-- ===================================================== -->
    <!-- SEARCH FORM                                           -->
    <!-- ===================================================== -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">

        <form method="GET" class="flex flex-col md:flex-row gap-4">

            <input type="text" name="search"
                   value="<?php echo htmlspecialchars($search); ?>"
                   placeholder="Enter Farmer ID, Username, or Name"
                   class="flex-1 border border-gray-300 rounded-md p-2">

            <div class="flex gap-2">
                <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded">
                    Search
                </button>
                <a href="/staff/search-farmer.php"
                   class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-6 rounded">
                    Clear
                </a>
            </div>

        </form>

        <p class="mt-2 text-sm text-gray-500">
            Tip: enter a number to search by Farmer ID, or enter a name to search by username or full name.
        </p>

    </div>

    <!-- ===================================================== -->
    <!-- SINGLE RESULT — FARMER PROFILE                        -->
    <!-- ===================================================== -->
    <?php if ($farmer != null): ?>

        <div class="bg-white rounded-lg shadow p-6">

            <h2 class="text-2xl font-bold text-gray-800">
                <?php echo htmlspecialchars($farmer['full_name']); ?>
            </h2>

            <div class="mt-2 text-gray-600 space-y-1">
                <p><span class="font-semibold">Farmer ID:</span> <?php echo $farmer['id']; ?></p>
                <p><span class="font-semibold">Username:</span> <?php echo htmlspecialchars($farmer['username']); ?></p>
                <p><span class="font-semibold">Phone:</span> <?php echo htmlspecialchars($farmer['phone']); ?></p>
                <p><span class="font-semibold">Address:</span> <?php echo htmlspecialchars($farmer['address']); ?></p>
                <p><span class="font-semibold">Join Date:</span>
                    <?php echo date('d-M-Y', strtotime($farmer['join_date'])); ?>
                </p>
                <p>
                    <span class="font-semibold">Status:</span>
                    <span class="px-2 py-1 text-xs rounded-full
                        <?php echo ($farmer['status'] === 'active') ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                        <?php echo ucfirst($farmer['status']); ?>
                    </span>
                </p>
            </div>

            <!-- Quick stats -->
            <div class="grid grid-cols-2 gap-4 mt-4">
                <div class="bg-blue-50 rounded p-3">
                    <p class="text-xs text-blue-700 uppercase">Milk Entries</p>
                    <p class="text-xl font-bold text-blue-900"><?php echo $milkCount; ?></p>
                </div>
                <div class="bg-green-50 rounded p-3">
                    <p class="text-xs text-green-700 uppercase">Dana Entries</p>
                    <p class="text-xl font-bold text-green-900"><?php echo $danaCount; ?></p>
                </div>
            </div>

            <!-- Quick action buttons — pre-fill the farmer -->
            <div class="mt-6 flex flex-wrap gap-2">
                <a href="/staff/entry-milk.php?farmer_id=<?php echo $farmer['id']; ?>"
                   class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Enter Milk for this Farmer
                </a>
                <a href="/staff/entry-dana.php?farmer_id=<?php echo $farmer['id']; ?>"
                   class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                    Enter Dana for this Farmer
                </a>
            </div>

        </div>

    <!-- ===================================================== -->
    <!-- MULTIPLE RESULTS — LIST                               -->
    <!-- ===================================================== -->
    <?php elseif ($search != '' && $results != null && $results->num_rows > 1): ?>

        <div class="bg-white rounded-lg shadow overflow-hidden">

            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-700">
                    Search Results (<?php echo $results->num_rows; ?> matches)
                </h2>
                <p class="text-sm text-gray-500 mt-1">Click a farmer to view details.</p>
            </div>

            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Username</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">

                <?php while ($row = $results->fetch_assoc()): ?>

                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm"><?php echo $row['id']; ?></td>
                        <td class="px-4 py-3 text-sm"><?php echo htmlspecialchars($row['username']); ?></td>
                        <td class="px-4 py-3 text-sm font-medium"><?php echo htmlspecialchars($row['full_name']); ?></td>
                        <td class="px-4 py-3 text-sm">
                            <a href="/staff/search-farmer.php?search=<?php echo $row['id']; ?>"
                               class="text-blue-600 hover:underline">
                                View
                            </a>
                        </td>
                    </tr>

                <?php endwhile; ?>

                </tbody>
            </table>

        </div>

    <!-- ===================================================== -->
    <!-- NO RESULTS                                            -->
    <!-- ===================================================== -->
    <?php elseif ($search != ''): ?>

        <div class="bg-yellow-100 border-l-4 border-yellow-500 p-4 rounded">
            <p class="font-semibold text-yellow-800">No farmer found.</p>
            <p class="text-sm text-yellow-700 mt-1">
                No farmer matches "<?php echo htmlspecialchars($search); ?>".
                Try a different ID, username, or name.
            </p>
        </div>

    <?php endif; ?>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>