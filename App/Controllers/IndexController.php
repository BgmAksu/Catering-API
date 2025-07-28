<?php

namespace App\Controllers;

use App\Plugins\Http\Response as Status;
use App\Plugins\Di\Factory;
use App\Plugins\Http\Exceptions;

class IndexController extends BaseController {
    /**
     * Controller function used to test whether the project was set up properly.
     * @return void
     */
    public function test() {
        // Respond with 200 (OK):
        (new Status\Ok(['message' => 'Hello world!']))->send();
    }

    public function testdb() {
        $di = Factory::getDi();
        $db = $di->getShared('db');

        // Örnek sorgu (tablo yok şu an, sadece demo)
        $result = $db->executeQuery("SELECT NOW() as 'current_time'");
        if ($result) {
            $stmt = $db->getStatement();
            $data = $stmt->fetch(\PDO::FETCH_ASSOC);
            echo json_encode(['time' => $data['current_time']]);
        } else {
            echo json_encode(['error' => 'Query failed']);
        }
    }
}
