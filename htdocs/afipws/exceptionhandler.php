<?php
set_exception_handler('exception_handler');

function exception_handler(Throwable $e) //?CAMBIO TOMAS (Version) -> Cambio Exception por Throwable. En v8.2php este puede recibir varios tipos de errores a dif de Exception
{

  //  require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
    //echo $e->getFile().':'.$e->getLine().'  ' . $e->getMessage();






   // $content = get_content(); //generic function;
    if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {

        header('Location: '.$_SERVER['REQUEST_URI']);
        setEventMessage($e->getMessage(),"errors");

    }else {

        ob_clean();//limpio buffers
          echo json_encode(array(
            'error' => array(
                'msg' => $e->getMessage(),
                'code' => $e->getCode()
            ),
        ));


    }

}
?>