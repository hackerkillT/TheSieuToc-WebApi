<?php
include(__DIR__ ."/api/config.php");
include(__DIR__ ."/api/function.php");

$napthe = new napthe($apikey);
$validate = $napthe->ValidateCallback($_POST);

if($validate != false) { // Nếu xác thực callback đúng thì chạy vào đây.
    $status = $validate['status']; // Trạng thái thẻ nạp, thẻ thành công = thanhcong , Thẻ sai, thẻ sai mệnh giá = thatbai
    $serial = $validate['serial']; // Số serial của thẻ.
    $pin = $validate['pin']; // Mã pin của thẻ.
    $card_type = $validate['card_type']; // Loại thẻ. vd: Viettel, Mobifone, Vinaphone.
    $amount = $validate['amount']; // Mệnh giá của thẻ. nếu bạn sài thêm hàm sai mệnh giá vui lòng sử dụng thêm hàm này tự cập nhật mệnh giá thật kèm theo desc là mệnh giá cũ
    $real_amount = $validate['real_amount']; // Thực nhận đã trừ chiết khấu
    $content = $validate['content']; // id transaction 

    // Lọc input trước khi xử lý
    $content = $conn->real_escape_string($content);
    $pin = $conn->real_escape_string($pin);
    $serial = $conn->real_escape_string($serial);
    $card_type = $conn->real_escape_string($card_type);
    $status = $conn->real_escape_string($status);

    $query = "SELECT * FROM trans_log WHERE status = 0 AND trans_id = ? AND pin = ? AND seri = ? AND type = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssss", $content, $pin, $serial, $card_type);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        print_r($row);
        if ($status == 'thanhcong') {
            // Xử lý nạp thẻ thành công tại đây.
            $update_query = "UPDATE trans_log SET status = 1 WHERE id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("i", $row['id']);
            $update_stmt->execute();
            $update_stmt->close();
        } elseif ($status == 'saimenhgia') {
            // Xử lý nạp thẻ sai mệnh giá tại đây.
            $update_query = "UPDATE trans_log SET status = 3, amount = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("ii", $amount, $row['id']);
            $update_stmt->execute();
            $update_stmt->close();
        } else {
            // Xử lý nạp thẻ thất bại tại đây.
            $update_query = "UPDATE trans_log SET status = 2 WHERE id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("i", $row['id']);
            $update_stmt->execute();
            $update_stmt->close();
        }

        // Lưu log Nạp Thẻ
        $file = "card.log";
        $fh = fopen($file, 'a') or die("Can't open file");
        fwrite($fh, "Tai khoan: " . $row['name'] . ", data: " . json_encode($_POST) . "\r\n");
        fclose($fh);
    }
}
?>
