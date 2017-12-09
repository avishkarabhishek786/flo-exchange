<?php
/**
 * Created by PhpStorm.
 * User: Abhishek Kumar Sinha
 * Date: 9/27/2017
 * Time: 3:22 PM
 */

require_once '../includes/imp_files.php';

if (!checkLoginStatus()) {
    return false;
}

if (isset($_POST['task']) && trim($_POST['task'])=='loadMyOrdersList') {

    $iter = "";
    if (isset($OrderClass, $user_id)) {

    $myOrders = $OrderClass->UserOrdersList($user_id, 0, 10);

    if (is_array($myOrders) && !empty($myOrders)) {

     foreach($myOrders as $myOrder):

        switch ($myOrder->OrderStatusId) {
            case '0':
                $status = 'Cancelled';
                break;
            case '1':
                $status = 'Successful';
                break;
            case '2':
                $status = 'Pending';
                break;
            case '3':
                $status = 'Pending';
                break;
            default:
                $status = 'Pending';
        }

        if($myOrder->OrderStatusId == '1') {
            $status = 'Successful';
        } else if ($myOrder->OrderStatusId == '2') {
            $status = 'Pending';
        } else if ($myOrder->OrderStatusId == '3'){
            $status = 'Pending';
        } else if($myOrder->OrderStatusId == '0') {
            $status = 'Cancelled';
        }

        if($myOrder->OrderTypeId == '1') {
            $OrderType = 'Sell';
        } elseif($myOrder->OrderTypeId == '0') {
            $OrderType = 'Buy';
        }

         $iter .= "<tr>";
         $iter .= "<td>$myOrder->Price</td>";
         $iter .= "<td>$myOrder->Quantity</td>";
         $iter .= "<td>";
         if(trim($status) == 'Pending') {
             $iter .= "<button class='btn-danger del_order' id='del_$myOrder->OrderId'>Cancel</button>";
         }
         $iter .= "</td>";
         $iter .= "<td>$myOrder->OfferAssetTypeId</td>";
         $iter .= "<td>$myOrder->WantAssetTypeId</td>";
         $iter .= "<td>$status</td>";
         $iter .= "<td>".date('d M, Y h:i:sa', strtotime($myOrder->InsertDate))."</td>";
         $iter .= "</tr>";
     endforeach;
        }
    }
    echo $iter;
} else {
    return false;
}