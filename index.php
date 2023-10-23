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

        $dom = $this->get_html($brandUrl);

        $brandInfo['id'] = $brand['id'];
        $brandInfo['brand'] = $this->get_brandName($dom);
        $brandInfo['description'] = $this->get_description($dom);
        $brandInfo['brand_logo'] = $this->get_imageLogo($dom);
        $brandInfo['brand_sample'] = $this->get_imageSample($dom, 0);
        $brandInfo['info'] = $this->get_info($dom);


        //Переписапть
        /*
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
        */




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
            echo 'Ошибка выполнения запроса cURL: ' . curl_error($ch);
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

    private function get_imageLogo($dom)
    {
        $imgDivs = $dom->getElementsByTagName('div');
        foreach ($imgDivs as $div) {
            if ($div->getAttribute('class') === 'manufacturer-logo-and-name-block text-center') {
                $imgTag = $div->getElementsByTagName('img')->item(0);
                if ($imgTag) {
                    $imageUrl = $imgTag->getAttribute('src');
                    return  $this->baseUrl . $imageUrl;
                }
            }
        }
        return null;
    }
    private function get_imageSample($dom)
    {
        $imgDivs = $dom->getElementsByTagName('div');
        foreach ($imgDivs as $div) {
            if ($div->getAttribute('class') === 'manufacturer-sample-photo text-center') {
                $imgTag = $div->getElementsByTagName('img')->item(0);
                if ($imgTag) {
                    $imageUrl = $imgTag->getAttribute('src');
                    return  $this->baseUrl . $imageUrl;
                }
            }
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

    private function get_attributeByTag($domElement, $tagName, $index, $attribute) {
        $elements = $domElement->getElementsByTagName($tagName);

        if($elements->length > $index) {
            return $elements->item($index)->getAttribute($attribute);
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