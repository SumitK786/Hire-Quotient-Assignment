<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ADMIN DASHBOARD</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 1.5rem;
            background-color: #f4f4f4;
        }

        header {
            background-color: #333;
            color: #fff;
            text-align: center;
            padding: 10px 0;
        }

        main {
            max-width: 800px;
            margin: 20px auto;
            background-color: #fff;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }

        .pagination button {
            margin: 0 5px;
            padding: 5px 10px;
            cursor: pointer;
            background-color: #4caf50;
            color: #fff;
            border: none;
            border-radius: 3px;
        }

        .selected {
            background-color: #e0e0e0;
        }

        .search-container {
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .search-container form {
            display: flex;
        }

        .search-container input[type="text"] {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 3px;
            margin-right: 5px;
        }

        .search-container button {
            padding: 8px 15px;
            cursor: pointer;
            background-color: #007bff;
            color: #fff;
            border: none;
            border-radius: 5px;
        }

        .delete-selected-btn {
            
            padding: 8px 15px;
            cursor: pointer;
            background-color: #ff0000;
            color: #fff;
            border: none;
            border-radius: 3px;
        }
        .delete-btn{
            cursor: pointer;
            background-color: #ff0000;
            color: #fff;
            border: none;
            border-radius: 3px;
            
        }
        .edit-btn{
            background-color: grey;
            cursor: pointer;
            border: none;
            border-radius: 3px;

        }

        .editable {
            background-color: #ffffcc;
        }
    </style>
</head>
<body>
    

<?php
// Fetch data from the API
$url = 'https://geektrust.s3-ap-southeast-1.amazonaws.com/adminui-problem/members.json';
$data = json_decode(file_get_contents($url), true);

// Pagination 
$rowsPerPage = 10;
$totalRows = count($data);
$totalPages = ceil($totalRows / $rowsPerPage);
$currentPage = isset($_GET['page']) ? max(1, min($totalPages, intval($_GET['page']))) : 1;
$startIndex = ($currentPage - 1) * $rowsPerPage;

// Filter data 
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
$filteredData = array_filter($data, function ($user) use ($searchTerm) {
    return empty($searchTerm) ||
        stripos($user['id'], $searchTerm) !== false ||
        stripos($user['name'], $searchTerm) !== false ||
        stripos($user['email'], $searchTerm) !== false ||
        stripos($user['role'], $searchTerm) !== false;
});


//form
echo '<div class="search-container">';
echo '<form method="get">';
echo '<input type="text" name="search" placeholder="Search..." value="' . $searchTerm . '">';
echo '<button type="submit">&#128269; Search</button>';
echo '</form>';
// Delete Selected button
echo '<button class="delete-selected-btn" onclick="deleteSelected()"> &#128465; Delete Selected</button>';
echo '</div>';

$displayData = array_slice($filteredData, $startIndex, $rowsPerPage);



// Display data
if (!empty($displayData)) {
    echo '<table>';
    echo '<tr><th><input type="checkbox" id="selectAll"></th><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Action</th></tr>';

    foreach ($displayData as $user) {
        echo '<tr>';
        echo '<td><input type="checkbox" class="selectRow" data-id="' . $user['id'] . '"></td>';
        echo '<td>' . $user['id'] . '</td>';
        echo '<td>' . $user['name'] . '</td>';
        echo '<td>' . $user['email'] . '</td>';
        echo '<td>' . $user['role'] . '</td>';
        echo '<td><button class="edit-btn" data-id="' . $user['id'] . '" onclick="editRow(this)">&#9998;</button> <button class="delete-btn" onclick="deleteRow(this)">&#128465;</button></td>';
        echo '</tr>';
    }
    echo '</table>';

    echo '<div class="pagination">';
    for ($i = 1; $i <= $totalPages; $i++) {
        echo '<button onclick="location.href=\'?page=' . $i . '&search=' . urlencode($searchTerm) . '\'">' . $i . '</button>';
    }  
    
} else {
    echo 'No data available.';
}
?>


<!-- script for table operation -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const table = document.querySelector('table');
        const selectAllCheckbox = document.getElementById('selectAll');
        const selectRowCheckboxes = document.querySelectorAll('.selectRow');
        const editButtons = document.querySelectorAll('.edit-btn');
        const deleteButtons = document.querySelectorAll('.delete-btn');
        const deleteSelectedButton = document.querySelector('.delete-selected-btn');
        const searchButton = document.querySelector('.search-icon');

        function editRow(button) {
            const row = button.closest('tr');
            const userId = row.querySelectorAll('td')[1];
            const nameCell = row.querySelectorAll('td')[2];
            const emailCell = row.querySelectorAll('td')[3];
            const roleCell = row.querySelectorAll('td')[4];
            
            //cell editable 
            nameCell.contentEditable = true;
            emailCell.contentEditable = true;
            roleCell.contentEditable = true;

            //highlight the editable row
            row.classList.add('editable');

            //first editable cell
            nameCell.focus();

            button.innerText = 'Save';
            button.removeEventListener('click', editRow);
            button.addEventListener('click', function () {
                saveRow(this);
            });
        }

        function saveRow(button) {
            const row = button.closest('tr');
            const userId = row.querySelectorAll('td')[1];
            const nameCell = row.querySelectorAll('td')[2];
            const emailCell = row.querySelectorAll('td')[3];
            const roleCell = row.querySelectorAll('td')[4];

            // Save the edited content 
            const newName = nameCell.innerText;
            const newEmail = emailCell.innerText;
            const newRole = roleCell.innerText;

            nameCell.contentEditable = false;
            emailCell.contentEditable = false;
            roleCell.contentEditable = false;


            row.classList.remove('editable');

            button.innerHTML = '&#9998;';
            button.removeEventListener('click', saveRow);
            button.addEventListener('click', function () {
                editRow(this);
            });
        }


        function deleteRow(button) {
            const row = button.closest('tr');
            const rowIndex = row.rowIndex;

            const confirmDelete = confirm(`Are you sure you want to delete this user?`);

            if (confirmDelete) {

                // Remove row 
                table.deleteRow(rowIndex);
            }
        }

        function deleteSelected() {
            const selectedRows = Array.from(document.querySelectorAll('.selectRow:checked')).map(checkbox => {
                return checkbox.closest('tr');
            });

            if (selectedRows.length === 0) {
                alert('Please select at least one user to delete.');
                return;
            }

            const confirmDelete = confirm(`Are you sure you want to delete the selected users?`);

            if (confirmDelete) {
                // Remove selected rows 
                selectedRows.forEach(row => {
                    row.remove();
                });

                // Uncheck
                selectAllCheckbox.checked = false;
            }
        }

        // select/deselect
        selectAllCheckbox.addEventListener('change', function () {
            selectRowCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });

        // single row selection
        selectRowCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function () {
                selectAllCheckbox.checked = false;
            });
        });

        editButtons.forEach(button => {
            button.addEventListener('click', function () {
                editRow(this);
            });
        });

        deleteButtons.forEach(button => {
            button.addEventListener('click', function () {
                deleteRow(this);
            });
        });


        deleteSelectedButton.addEventListener('click', function () {
            deleteSelected();
        });
    });
</script>

</body>
</html>
