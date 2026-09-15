<?php

require_once __DIR__ . '/../includes/auth.php';
requireRole('staff');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$search = isset($_GET['search'])
    ? trim($_GET['search'])
    : '';

$results = null;
$farmer = null;


// Search farmer
if ($search !== '') {

    if (is_numeric($search)) {

        $farmerId = (int) $search;

        $stmt = $conn->prepare(
            "SELECT
                f.id,
                f.address,
                f.join_date,
                u.username,
                u.full_name,
                u.phone
             FROM farmers f
             JOIN users u ON f.user_id = u.id
             WHERE f.id = ?"
        );

        $stmt->bind_param('i', $farmerId);

    } else {

        $searchLike = '%' . $search . '%';

        $stmt = $conn->prepare(
            "SELECT
                f.id,
                f.address,
                f.join_date,
                u.username,
                u.full_name,
                u.phone
             FROM farmers f
             JOIN users u ON f.user_id = u.id
             WHERE u.username LIKE ?
                OR u.full_name LIKE ?
             ORDER BY u.full_name ASC
             LIMIT 20"
        );

        $stmt->bind_param(
            'ss',
            $searchLike,
            $searchLike
        );
    }

    $stmt->execute();
    $results = $stmt->get_result();

    if ($results->num_rows === 1) {
        $farmer = $results->fetch_assoc();
    }
}


include __DIR__ . '/../includes/header.php';

?>

<div class="max-w-7xl mx-auto px-4 py-6">

    <!-- Page title -->
    <div class="mb-6">

        <h1 class="text-2xl font-bold text-gray-800">
            Search Farmer
        </h1>

        <p class="text-sm text-gray-500 mt-1">
            Find a farmer by ID, username, or name
        </p>

    </div>


    <!-- Search form -->
    <div class="bg-white border border-gray-200 rounded-lg p-5 mb-6">

        <form
            method="GET"
            class="flex flex-col md:flex-row gap-3"
        >

            <input
                type="text"
                name="search"
                value="<?php echo e($search); ?>"
                placeholder="Enter Farmer ID, Username, or Name"
                class="flex-1 border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:border-blue-500"
            >

            <button
                type="submit"
                class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md"
            >
                Search
            </button>

            <a
                href="<?php echo BASE_URL; ?>/staff/search-farmer.php"
                class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-6 py-2 rounded-md text-center"
            >
                Clear
            </a>

        </form>

    </div>


    <!-- Single farmer -->
    <?php if ($farmer !== null): ?>

        <div class="bg-white border border-gray-200 rounded-lg p-6">

            <h2 class="text-xl font-bold text-gray-800">
                <?php echo e($farmer['full_name']); ?>
            </h2>


            <div class="mt-4 space-y-2 text-gray-600">

                <p>
                    <strong>Farmer ID:</strong>
                    <?php echo (int) $farmer['id']; ?>
                </p>

                <p>
                    <strong>Username:</strong>
                    <?php echo e($farmer['username']); ?>
                </p>

                <p>
                    <strong>Phone:</strong>
                    <?php echo e($farmer['phone']); ?>
                </p>

                <p>
                    <strong>Address:</strong>
                    <?php echo e($farmer['address']); ?>
                </p>

                <p>
                    <strong>Join Date:</strong>
                    <?php echo formatDate($farmer['join_date']); ?>
                </p>

            </div>


            <!-- Actions -->
            <div class="mt-6 flex flex-wrap gap-2">

                <a
                    href="<?php echo BASE_URL; ?>/staff/entry-milk.php?farmer_id=<?php echo (int) $farmer['id']; ?>"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-md"
                >
                    Enter Milk
                </a>

                <a
                    href="<?php echo BASE_URL; ?>/staff/entry-dana.php?farmer_id=<?php echo (int) $farmer['id']; ?>"
                    class="bg-green-600 hover:bg-green-700 text-white px-5 py-2 rounded-md"
                >
                    Enter Dana
                </a>

            </div>

        </div>


    <!-- Multiple farmers -->
    <?php elseif ($search !== '' && $results !== null && $results->num_rows > 1): ?>

        <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">

            <div class="px-5 py-4 border-b border-gray-200">

                <h2 class="font-semibold text-gray-800">
                    Search Results
                </h2>

                <p class="text-sm text-gray-500 mt-1">
                    <?php echo $results->num_rows; ?> farmers found
                </p>

            </div>


            <div class="overflow-x-auto">

                <table class="min-w-full text-sm">

                    <thead class="bg-gray-50 border-b">

                        <tr>

                            <th class="px-4 py-3 text-left">
                                ID
                            </th>

                            <th class="px-4 py-3 text-left">
                                Username
                            </th>

                            <th class="px-4 py-3 text-left">
                                Name
                            </th>

                            <th class="px-4 py-3 text-right">
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody class="divide-y divide-gray-100">

                        <?php while ($row = $results->fetch_assoc()): ?>

                            <tr class="hover:bg-gray-50">

                                <td class="px-4 py-3">
                                    <?php echo (int) $row['id']; ?>
                                </td>

                                <td class="px-4 py-3">
                                    <?php echo e($row['username']); ?>
                                </td>

                                <td class="px-4 py-3 font-medium">
                                    <?php echo e($row['full_name']); ?>
                                </td>

                                <td class="px-4 py-3 text-right">

                                    <a
                                        href="<?php echo BASE_URL; ?>/staff/search-farmer.php?search=<?php echo urlencode($row['id']); ?>"
                                        class="text-blue-600 hover:underline"
                                    >
                                        View
                                    </a>

                                </td>

                            </tr>

                        <?php endwhile; ?>

                    </tbody>

                </table>

            </div>

        </div>


    <!-- No farmer -->
    <?php elseif ($search !== ''): ?>

        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">

            <p class="font-medium text-yellow-800">
                No farmer found.
            </p>

        </div>

    <?php endif; ?>

</div>


<?php include __DIR__ . '/../includes/footer.php'; ?>