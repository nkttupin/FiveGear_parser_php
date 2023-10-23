<?php


$fivegear = new Fivegear();
// Вызов метода класса
$json = $fivegear->get('AmD');
echo $json;

class Fivegear
{
    private $baseUrl = 'https://xn--80aaaoea1ebkq6dxec.xn--p1ai';

    public function get($brandName)
    {
        $brandInfo = array();

        $catalog = ($this->get_catalog());
        foreach ($catalog as $brand) {
            if (strtolower($brand['name']) === strtolower($brandName)) {

                $brandInfo = ($this->parse_brand($brand));
                break;
            }
        }
        header('Content-Type: application/json; charset=utf-8');

        return json_encode($brandInfo, JSON_UNESCAPED_UNICODE);
    }

    private function parse_brand($brand)
    {
        $brandInfo = array();
        $brandUrl = $this->baseUrl . $brand['link'];

        $Info = $this->get_html($brandUrl);

        $brandInfo['id'] = $brand['id'];

        $dom = new DOMDocument();
        libxml_use_internal_errors(true); // Включаем внутренний обработчик ошибок libxml
        $dom->loadHTML($Info);
        libxml_use_internal_errors(false); // Выключаем внутренний обработчик ошибок libxml

        $h1 = $dom->getElementsByTagName('h1');
        if ($h1->length > 0) {
            $brandInfo['brand'] = $h1[0]->textContent;
        }
        // Получение описания
        $description = $dom->getElementsByTagName('div');
        foreach ($description as $div) {
            $classAttribute = $div->getAttribute('class');
            if ($classAttribute === 'manufacturer-info-description') {
                $p = $div->getElementsByTagName('p');
                if ($p->length > 0) {
                    $brandInfo['description'] = $p[0]->textContent;
                    break;
                }
            }
        }
        //Переписапть
        $logoImg = $dom->getElementsByTagName('img');
        if ($logoImg->length > 0) {
            $imageUrl = $logoImg[0]->getAttribute('src');
            $fullImageUrl = $this->baseUrl . $imageUrl;
            $brandInfo['brand_logo'] = $fullImageUrl;
        }
        $sampleImg = $dom->getElementsByTagName('img');
        if ($sampleImg->length > 0) {
            $sampleImageUrl = $sampleImg[0]->getAttribute('src');
            $fullSampleImageUrl = $this->baseUrl . $sampleImageUrl;
            $brandInfo['brand_sample'] = $fullSampleImageUrl;
        }
        $info = array();

        $table = $dom->getElementsByTagName('section')->item(0); // Находим первый элемент section

        if ($table) {
            $rows = $table->getElementsByTagName('div'); // Получаем все элементы div внутри секции

            $name = "";
            foreach ($rows as $row) {
                if ($row->getAttribute('class') === 'mfr-prop-name col-12 col-sm-6') {
                    $name = $row->textContent;
                    $name = trim($name);
                } elseif ($row->getAttribute('class') === 'mfr-prop-value col-12 col-sm-6') {
                    $value = $row->textContent;
                    if ($name === 'Страна происхождения:') {
                        $info['Страна происхождения'] = $value;
                    } elseif ($name === 'Наша оценка качества:') {
                        $info['Наша оценка качества'] = $value;
                    } elseif ($name === 'Конвейерный поставщик:') {
                        $info['Конвейерный поставщик'] = $value;
                    } elseif ($name === 'Специализация производителя:') {
                        $info['Специализация производителя'] = $value;
                    } elseif ($name === 'Способ производства:') {
                        $info['Способ производства'] = $value;
                    } elseif ($name === 'Эта компания - автопроизводитель:') {
                        $info['Эта компания - автопроизводитель'] = $value;
                    }

                } elseif ($row->getAttribute('class') === 'mfr-prop-value col-12 col-sm-6 links-cell') {
                if ($name === 'Ссылки на официальные сайты:'){
                    $links = $row->getElementsByTagName('a');
                    $urls = array();
                    foreach ($links as $link) {
                        $url = $link->getAttribute('href');
                        $urls[] = $url;
                    }
                    $info['Ссылки на официальные сайты'] = $urls;
                }
                }
            }

        }

        $brandInfo['info'] = $info;


        return $brandInfo;

    }

    private function get_catalog()
    {
        $brands = array();
        $id = 1;

        $response = $this->get_html(); // вызов метода curl_init()

        $dom = new DOMDocument();
        libxml_use_internal_errors(true); // Включаем внутренний обработчик ошибок libxml
        $dom->loadHTML($response);
        libxml_use_internal_errors(false); // Выключаем внутренний обработчик ошибок libxml

        $divElements = $dom->getElementsByTagName('div');

        foreach ($divElements as $divElement) {
            // Проверяем, содержит ли элемент класс 'col-12 col-sm-6 col-md-4 col-lg-3 mfr-link-cell'
            if ($divElement->getAttribute('class') === 'col-12 col-sm-6 col-md-4 col-lg-3 mfr-link-cell') {
                $aElement = $divElement->getElementsByTagName('a')->item(0); // Получаем первый элемент <a> внутри div

                $catalogName = $aElement->textContent; // Название каталога
                $catalogLink = $aElement->getAttribute('href'); // Ссылка на каталог

                $brands[] = array(
                    'id' => $id,
                    'name' => $catalogName,
                    'link' => $catalogLink
                );
                $id++;
            }
        }
        return $brands;
    }

    private function get_html($url = null)
    {
        $ch = curl_init();

        // Установка URL
        $url = isset($url) ? $url : $this->baseUrl . '/manufacturers';
        curl_setopt($ch, CURLOPT_URL, $url);
        // Установка CURLOPT_RETURNTRANSFER (вернуть ответ в виде строки)
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // Установка заголовков CORS
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Access-Control-Allow-Origin: *',
            'Access-Control-Allow-Methods: GET, POST, OPTIONS',
            'Access-Control-Allow-Headers: Origin, Content-Type, Accept',
        ]);

        // Выполнение запроса cURL
        $output = curl_exec($ch); // $output содержит полученную строку

        // Проверка на ошибки
        if ($output === false) {
            echo 'Ошибка выполнения запроса cURL: ' . curl_error($ch);
        }

        // закрытие сеанса curl для освобождения ресурсов
        curl_close($ch);

        return $output;
    }
}

?>