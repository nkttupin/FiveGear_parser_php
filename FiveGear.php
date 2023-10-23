<?php


$fivegear = new Fivegear();
$brandName = $_POST['directory_name'];
$response = $fivegear->get($brandName);

echo $response;


class Fivegear
{
    private $baseUrl = 'https://xn--80aaaoea1ebkq6dxec.xn--p1ai';

    public function get($brandName)
    {
        try {
            $output = [
                "status" => null,
                "result" => null
            ];

            $catalog = $this->get_catalog();
            foreach ($catalog as $brand) {
                if (strtolower($brand['name']) !== strtolower($brandName)) continue;

                $output["status"] = 200;
                $output["result"] = $this->parse_brand($brand);
                break;
            }

            // Возвращаем 404 статус, если бренд не найден
            if ($output["status"] === null) {
                $output["status"] = 404;
                $output["result"] = "Brand not found: " . $brandName;
            }
        } catch (Exception $e) {
            $output["status"] = 500;
            $output["result"] = "Server error: " . $e->getMessage();
        }

        header('Content-Type: application/json; charset=utf-8');
        return json_encode($output, JSON_UNESCAPED_UNICODE);
    }

    private function parse_brand($brand)
    {
        $brandInfo = array();
        $brandUrl = $this->baseUrl . $brand['link'];

        $dom = $this->get_html($brandUrl);

        $brandInfo['id'] = $brand['id'];
        $brandInfo['brand'] = $this->get_brandName($dom);
        $brandInfo['description'] = $this->get_description($dom);
        $brandInfo['brand_logo'] = $this->getImageByClass($dom, 'manufacturer-logo-and-name-block text-center');
        $brandInfo['brand_sample'] = $this->getImageByClass($dom, 'manufacturer-sample-photo text-center');
        $brandInfo['info'] = $this->get_info($dom);

        return $brandInfo;

    }

    private function get_catalog()
    {
        $brands = array();
        $id = 1;
        $dom = $this->get_html();

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
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Access-Control-Allow-Origin: *',
            'Access-Control-Allow-Methods: GET, POST, OPTIONS',
            'Access-Control-Allow-Headers: Origin, Content-Type, Accept',
        ]);

        $output = curl_exec($ch); // $output содержит полученную строку

        if ($output === false) {
            throw new Exception('cURL error: ' . curl_error($ch));
        }

        curl_close($ch);

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($output);
        libxml_use_internal_errors(false);

        return $dom;
    }

    private function get_brandName($dom)
    {
        return $this->get_innerHTMLByTag($dom, 'h1', 0);
    }
    private function getImageByClass($dom, $className) {
        $divs = $dom->getElementsByTagName('div');

        foreach($divs as $div) {
            if($div->getAttribute('class') !== $className ) continue;

            $imgTag = $div->getElementsByTagName('img')->item(0);
            if (!isset($imgTag)) continue;

            $imageUrl = $imgTag->getAttribute('src');
            return $this->baseUrl . $imageUrl;
        }

        return null;
    }

    private function get_description($dom) {
        $descriptionDivs = $dom->getElementsByTagName('div');

        foreach($descriptionDivs as $div) {
            if($div->getAttribute('class') === 'manufacturer-info-description') {
                return $this->get_innerHTMLByTag($div, 'p', 0);
            }
        }

        return null;
    }

    private function get_innerHTMLByTag($domElement, $tagName, $index) {
        $elements = $domElement->getElementsByTagName($tagName);

        if($elements->length > $index) {
            return $elements->item($index)->textContent;
        }

        return null;
    }

    private function get_info($dom) {
        $info = array();
        $section  = $dom->getElementsByTagName('section')->item(0);

        if($section) {
            $rows = $section->getElementsByTagName('div'); // Получаем все элементы div внутри секции

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

        return $info;
    }

}

?>