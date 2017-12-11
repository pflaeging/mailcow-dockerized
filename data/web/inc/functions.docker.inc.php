<?php
function docker($service_name, $action, $attr1 = null, $attr2 = null, $extra_headers = null) {
  if ($_SESSION['mailcow_cc_role'] != "admin") {
    $_SESSION['return'] = array(
      'type' => 'danger',
      'msg' => sprintf($lang['danger']['access_denied'])
    );
    return false;
  }
  $curl = curl_init();
  curl_setopt($curl, CURLOPT_HTTPHEADER,array( 'Content-Type: application/json' ));
  switch($action) {
    case 'get_id':
      curl_setopt($curl, CURLOPT_URL, 'http://dockerapi:8080/containers/json');
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($curl, CURLOPT_POST, 0);
      $response = curl_exec($curl);
      if ($response === false) {
        $err = curl_error($curl);
        curl_close($curl);
        return $err;
      }
      else {
        curl_close($curl);
        $containers = json_decode($response, true);
        if (!empty($containers)) {
          foreach ($containers as $container) {
            if ($container['Config']['Labels']['com.docker.compose.service'] == $service_name) {
              return trim($container['Id']);
            }
          }
        }
      }
      return false;
    break;
    case 'info':
      $container_id = docker($service_name, 'get_id');
      if (ctype_xdigit($container_id)) {
        curl_setopt($curl, CURLOPT_URL, 'http://dockerapi:8080/containers/' . $container_id . '/json');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POST, 0);
        $response = curl_exec($curl);
        if ($response === false) {
          $err = curl_error($curl);
          curl_close($curl);
          return $err;
        }
        else {
          curl_close($curl);
          if (empty($response)) {
            return true;
          }
          else {
            return json_decode($response, true);
          }
        }
      }
      else {
        return false;
      }
    break;
    case 'post':
      if (!empty($attr1)) {
        $container_id = docker($service_name, 'get_id');
        if (ctype_xdigit($container_id) && ctype_alnum($attr1)) {
          curl_setopt($curl, CURLOPT_URL, 'http://dockerapi:8080/containers/' . $container_id . '/' . $attr1);
          curl_setopt($curl, CURLOPT_POST, 1);
          if (!empty($attr2)) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($attr2));
          }
          if (!empty($extra_headers) && is_array($extra_headers)) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $extra_headers);
          }
          curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
          $response = curl_exec($curl);
          if ($response === false) {
            $err = curl_error($curl);
            curl_close($curl);
            return $err;
          }
          else {
            curl_close($curl);
            if (empty($response)) {
              return true;
            }
            else {
              return $response;
            }
          }
        }
      }
    break;
  }
}