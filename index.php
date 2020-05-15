<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

$GLOBALS['db_link'] = new mysqli("remotemysql.com", "d4DubyxbEm", "ZvpYodSAGs", "d4DubyxbEm");

$API_URL = explode(
    "?",
    strtoupper(rtrim(str_replace("index.php", "", $_SERVER['REQUEST_URI']), '/'))
);

$response = new stdClass();
$response->data = null;
$response->error = null;
$result = [];
$gotUrl = true;

switch ($API_URL[1]) {
    case 'MENU':
        $result = getMenu($_REQUEST);
        break;
    case 'CART':
        $result = postIntoCart();
        break;
    default:
        $gotUrl = false;
}

if (!$gotUrl) {
    $response->error = new stdClass();
    $response->error->code = '1984';
    $response->error->title = "Your request was incorrect!";
}

if ($result['length']) {
    $response->data = $result['data'];
}

$responseJSON = json_encode($response);
echo ($responseJSON);

function getMenu() # отправляем приложению информацию о имеющихся в наличии
{
    $res = $GLOBALS['db_link']->query("SELECT * FROM menu");
    $row = $res->fetch_all();
    return array("data" => $row, "length" => count($row));
}

function postIntoCart() # кладём в базу информацию о заказе (валидация отсутствует, если что)
{
    $customer_id = $_POST['customer_id'];
    $address = $_POST['address'];
    $phone = $_POST['phone'];
    $cart = json_decode($_POST['cart'], $assoc=TRUE);
    $cart_title = [];
    $cart_amount = [];
    $total_sum = 0;

    for ($i = 0; $i < count($cart); $i++) {
        $total_sum += $cart[$i]['amount'] * $cart[$i]['price'];
        array_push($cart_title, $cart[$i]['item']);
        array_push($cart_amount, $cart[$i]['amount']);
    }

    $finishSum = $total_sum < 190 ? $total_sum + 10 : $total_sum; # учитываем доставку

    $sql = sprintf( # создаём форматированную строку SQL-запроса на основе данных из $_FILES
        "INSERT INTO orders (customer_id, cart_title, cart_amount, total_sum, address, phone) values ('%s', '%s', '%s', '%d', '%s', '%s')",
        $customer_id,
        json_encode($cart_title),
        json_encode($cart_amount),
        $finishSum,
        $address,
        $phone
    );

    $GLOBALS['db_link']->query($sql);

    header('location: http://derevoxp.ru/success'); # после загрузки редиректим юзера обратно на страницу с формой

}
