<?php
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

// разумеется это должно быть вынесено например в .env
$dbHost = 'postgres';
$dbPort = 5432;
$dbName = 'appdb';
$dbUser = 'root';
$dbPass = 'rootpass';

function getPdo(string $dbHost, int $dbPort, string $dbName, string $dbUser, string $dbPass): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf('pgsql:host=%s;port=%d;dbname=%s', $dbHost, $dbPort, $dbName);

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);

    return $pdo;
}

if (isset($_GET['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    $action = $_GET['action'];

    try {
        if ($action === 'autocomplete') {
            $query = isset($_GET['q']) ? trim((string)$_GET['q']) : '';

            if (mb_strlen($query, 'UTF-8') < 4) {
                echo json_encode(['items' => []], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $pdo = getPdo($dbHost, $dbPort, $dbName, $dbUser, $dbPass);

            $sql = 'SELECT aoguid, officialname
                    FROM address_objects
                    WHERE officialname ILIKE :pattern
                    ORDER BY officialname
                    LIMIT 10';

            $stmt = $pdo->prepare($sql);
            $pattern = '%' . $query . '%';
            $stmt->bindParam(':pattern', $pattern, PDO::PARAM_STR);
            $stmt->execute();

            $results = $stmt->fetchAll();

            echo json_encode(['items' => $results], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($action === 'select') {
            $data = [
                'success' => true,
                'fields'  => $_POST,
            ];

            echo json_encode($data, JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Неизвестное действие
        http_response_code(400);
        echo json_encode(['error' => 'Unknown action'], JSON_UNESCAPED_UNICODE);
        exit;
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode([
            'error'   => 'Server error',
            'message' => $e->getMessage(),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="color-scheme" content="dark light">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Поиск адресов</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">

    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f8f9fa;
        }

        .search-card {
            width: 100%;
            max-width: 600px;
        }

        .autocomplete-list {
            position: absolute;
            z-index: 1000;
            width: 100%;
            max-height: 300px;
            overflow-y: auto;
        }
        #result {
            display: none;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow search-card">
                <div class="card-body">
                    <h5 class="card-title mb-3">Поиск адресов</h5>
                    <form id="address-form" autocomplete="off">
                        <div class="mb-3 position-relative">
                            <input type="text" class="form-control" id="address-input" name="officialname"
                                   placeholder="Начните вводить адрес" />
                            <input type="hidden" id="address-aoguid" name="aoguid" />
                            <div id="autocomplete-container" class="autocomplete-list d-none">
                                <div class="list-group" id="autocomplete-list"></div>
                            </div>
                        </div>
                    </form>
                    <button type="button" id="select-button" class="btn btn-primary">Выбрать</button>

                    <hr class="my-4" />

                    <div id="result">
                        <h6>Выбранный адрес:</h6>
                        <p id="selected-address" class="fw-semibold text-primary"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>

<script>
    (function ($) {
        const $input = $('#address-input');
        let $aoguid = $('#address-aoguid');
        const $autocompleteContainer = $('#autocomplete-container');
        const $autocompleteList = $('#autocomplete-list');
        const $selectedAddressWrapper = $('#result');
        const $selectedAddress = $('#selected-address');
        const $selectButton = $('#select-button');
        const $form = $('#address-form');

        let debounceTimer = null;

        function hideAutocomplete() {
            $autocompleteContainer.addClass('d-none');
            $autocompleteList.empty();
        }

        function showAutocomplete(items) {
            $autocompleteList.empty();

            if (!items || !items.length) {
                const $empty = $('<div class="list-group-item text-muted"></div>');
                $empty.text('Ничего не найдено');
                $autocompleteList.append($empty);
                $autocompleteContainer.removeClass('d-none');
                return;
            }

            items.forEach(function (item) {
                const text = item.officialname || '';
                const id = item.aoguid || '';

                const $el = $('<button type="button" class="list-group-item list-group-item-action"></button>');
                $el.text(text);
                $el.data('officialname', text);
                $el.data('aoguid', id);

                $autocompleteList.append($el);
            });

            $autocompleteContainer.removeClass('d-none');
        }

        function fetchAutocomplete(query) {
            if (query.length < 4) {
                hideAutocomplete();
                return;
            }

            $.ajax({
                url: '?action=autocomplete',
                method: 'GET',
                dataType: 'json',
                data: {q: query}
            }).done(function (response) {
                if (response && Array.isArray(response.items)) {
                    showAutocomplete(response.items);
                } else {
                    hideAutocomplete();
                }
            }).fail(function () {
                hideAutocomplete();
            });
        }

        $input.on('input', function () {
            const value = $(this).val().toString();
            $aoguid.val('');

            if (debounceTimer) {
                clearTimeout(debounceTimer);
            }

            // это нужно для устранения эффекта "дребезг контактов"
            debounceTimer = setTimeout(function () {
                fetchAutocomplete(value);
            }, 500);
        });

        $autocompleteList.on('click', '.list-group-item', function () {
            const officialname = $(this).data('officialname') || '';
            const id = $(this).data('aoguid') || '';

            $input.val(officialname);
            $aoguid.val(id);
            hideAutocomplete();
            $selectedAddress.text(officialname);
        });

        $selectButton.on('click',function () {
            $selectedAddressWrapper.show();
        });

        $(document).on('click', function (event) {
            const $target = $(event.target);
            if (!$target.closest('.position-relative').length) {
                hideAutocomplete();
            }
        });

        $selectButton.on('click', function () {
            const formData = $form.serialize();

            $.ajax({
                url: '?action=select',
                method: 'POST',
                dataType: 'json',
                data: formData
            }).done(function (response) {
                if (response && response.fields && response.fields.officialname) {
                    $selectedAddress.text(response.fields.officialname);
                }
            });
        });
    })(jQuery);
</script>

</body>
</html>
