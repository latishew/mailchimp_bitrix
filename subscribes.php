<?

// for MailChimp API v3.0

/**
 * Класс для работы с АПИ
 *
 * Class IldarMailchimp
 */
class IldarMailchimp
{
    
    /**
     * АПИ ключь
     *
     * @var
     */
    protected $api_key;
    
    /**
     * Адрес запроса к апи
     *
     * @var string
     */
    protected $url = 'https://<dc>.api.mailchimp.com/3.0/';
    
    /**
     * AppjobsMailchimp constructor.
     *
     * @param $key
     * @param $dc
     */
    public function __construct($key, $dc)
    {
        $this->api_key = $key;
        $this->url     = str_replace('<dc>', $dc, $this->url);
    }
    
    
    /**
     * Выполенение запроса к АПИ
     *
     * @param string     $method метод запроса
     * @param array|bool $data   Данные
     *
     * @return mixed
     */
    protected function request($method = '', $type = 'post', $data = false)
    {
        $url = $this->url . $method;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Accept: application/vnd.api+json',
            'Content-Type: application/vnd.api+json',
            'Authorization: apikey ' . $this->api_key
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        switch ($type) {
            case 'post':
                curl_setopt($ch, CURLOPT_POST, true);
                if (is_array($data)) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                }
                break;
            
            case 'get':
                $query = http_build_query($data, '', '&');
                curl_setopt($ch, CURLOPT_URL, $url . '?' . $query);
                break;
            
            case 'delete':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
            
            case 'patch':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
                if (is_array($data)) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                }
                break;
            
            case 'put':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                if (is_array($data)) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                }
                break;
        }
        
        $out = curl_exec($ch);     
        curl_close($ch);        
        return json_decode($out);
    }
    
    
    /**
     * Получить список доступных листов рассылки
     *
     * @return array|mixed|object
     */
    public function getLists()
    {
        return $this->request('lists', 'get');
    }
    
    
    /**
     * Добаление подписчика
     *
     * @param string $list
     * @param string $email
     * @param string $fname
     * @param string $lname
     *
     * @return string
     */
    public function addSubscriber($list = '', $email = '', $fname = '', $lname = '')
    {
        $data = array(
            'email_address' => $email,
            'status'        => 'subscribed',
            'merge_fields'  => array('FNAME' => $fname, 'LNAME' => $lname)
        );
        
        $res = $this->request('lists/' . $list . '/members', 'post', $data);
        
        $return = 'На указанную почту придет письмо с подтвержением подписки.';
        
        if ($res->status == 400) {
            switch ($res->title) {
                case 'Member Exists':
                    $return = 'Вы уже подписались ранее';
                    break;
                
                default:
                    $return = $res->title;
                    break;
            }
        }
        
        return $return;
    }
    
    /**
     * Списко компаний
     *
     * @return mixed
     */
    public function getCampaigns()
    {
        $res = $this->request('campaigns', 'get');
        
        return $res;
    }
    
    
    /**
     * Создание компании
     *
     * @param string $list_id
     * @param string $subj
     * @param string $from_name
     * @param string $reply_to
     * @param int    $segment_id
     *
     * @return mixed
     */
    public function createCamping($list_id = '', $subj, $from_name, $reply_to, $segment_id = 0)
    {
        $data = array(
            'type'       => 'regular',
            'recipients' => array('list_id' => $list_id),
            'settings'   => array(
                'subject_line' => $subj,
                'reply_to'     => $reply_to,
                'from_name'    => $from_name
            )
        );
        
        if ($segment_id !== 0) {
            $data['recipients'] = array(
                'list_id'      => $list_id,
                'segment_opts' => array('saved_segment_id' => $segment_id)
            );
        }
        
        $res = $this->request('campaigns', 'post', $data);
        
        return $res;
    }
    
    /**
     * Создание текста для компании
     *
     * @param $html
     * @param $id_camping
     *
     * @return mixed
     */
    public function createCampingContent($plain, $html, $id_camping)
    {
        $data = array('plain_text' => $plain, 'html' => $html);
        
        $res = $this->request('campaigns/' . $id_camping . '/content', 'put', $data);
        
        return $res;
    }
    
    
    /**
     * Отправка тестового письма для компании
     *
     * @param string $id_camping компании
     * @param string $email      куда отправлять тестовое сообщение
     *
     * @return mixed
     */
    public function testCamping($id_camping, $email = '')
    {
        $data = array('test_emails' => array($email), 'send_type' => 'html');
        
        $res = $this->request('campaigns/' . $id_camping . '/actions/test', 'post', $data);
        
        return $res;
    }
    
    
    /**
     * Отправка компании
     *
     * @param $id_camping id компани
     */
    public function sendCamping($id_camping = '')
    {
        $res = $this->request('campaigns/' . $id_camping . '/actions/send', 'post');
        
        return $res;
    }
    
    /**
     * Получить список доступных шаблонов
     *
     * @return mixed
     */
    public function getTemplates()
    {
        $res = $this->request('templates/', 'get');
        
        return $res;
    }
    
    
    /**
     * Создание сегмента
     *
     * @param string $name
     * @param string $list_id
     * @param array  $cond
     *
     * @return mixed
     */
    public function createSegment($name = '', $list_id = '', $cond = array())
    {
        $data = array('name' => $name, 'options' => $cond);
        
        $res = $this->request('lists/' . $list_id . '/segments', 'post', $data);
        
        return $res;
    }
    
    
    /**
     * Получить все сегменты в листе
     *
     * @param string $list_id
     *
     * @return array
     */
    public function getListSegments($list_id = '')
    {
        $segments = $this->request('lists/' . $list_id . '/segments', 'get');
        
        $res = array();
        
        foreach ($segments->segments as $segment_itm) {
            $res[$segment_itm->id] = $segment_itm->name;
        }
        
        return $res;
    }

    // функция рассылки дописана, та которая выше не отрабатывает
    public function mc_request( $key, $target, $data = false )
    {
        $ch = curl_init("https://us16.api.mailchimp.com/3.0/".$target);    
        
        curl_setopt( $ch, CURLOPT_HTTPHEADER, array
        (
                'Accept: application/vnd.api+json',
                'Content-Type: application/vnd.api+json',
                'Authorization: apikey ' . $key
        ) );
     
        curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'POST' );
        //curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, $type );
        curl_setopt( $ch, CURLOPT_TIMEOUT, 5 );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_USERAGENT, 'YOUR-USER-AGENT' );
     
        if( $data )
            curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $data ) );
     
        $response = curl_exec( $ch );
        curl_close( $ch );        
        return $response;
    }
}

    


AddEventHandler("iblock", "OnBeforeIBlockElementAdd", Array("IblockActionsHadlers", "OnBeforeIBlockElementAddHandler"));
AddEventHandler("iblock", "OnBeforeIBlockElementUpdate", Array("IblockActionsHadlers", "OnBeforeIBlockElementAddHandler"));

class IblockActionsHadlers
{

    function OnBeforeIBlockElementAddHandler(&$arFields)
    {
        if (($arFields["IBLOCK_ID"] == CIBlockTools::GetIBlockId("shares")))
        {   
            if($arFields["PROPERTY_VALUES"][267][0]["VALUE"] == 64){

$html = <<<EOT
      <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en" style="background-color:#f1f1f1!important">  <head>    <meta http-equiv="content-type" content="text/html; charset=utf-8">    <meta name="viewport" content="width=device-width">    <title>$arFields[NAME]</title>      <style type="text/css">    @media only screen{     html{           min-height:100%;            background:#f3f3f3;     }}  @media only screen and (max-width:766px){       .small-float-center{            margin:0 auto !important;           float:none !important;          text-align:center !important;       }}  @media only screen and (max-width:766px){       .small-text-center{         text-align:center !important;       }}  @media only screen and (max-width:766px){       table.body img{         width:auto;         height:auto;        }}  @media only screen and (max-width:766px){       table.body center{          min-width:0 !important;     }}  @media only screen and (max-width:766px){       table.body .container{          width:95% !important;       }}  @media only screen and (max-width:766px){       table.body .columns{            height:auto !important;         -moz-box-sizing:border-box;         -webkit-box-sizing:border-box;          box-sizing:border-box;          padding-left:16px !important;           padding-right:16px !important;      }}  @media only screen and (max-width:766px){       table.body .columns .columns{           padding-left:0 !important;          padding-right:0 !important;     }}  @media only screen and (max-width:766px){       table.body .collapse .columns{          padding-left:0 !important;          padding-right:0 !important;     }}  @media only screen and (max-width:766px){       th.small-12{            display:inline-block !important;            width:100% !important;      }}  @media only screen and (max-width:766px){       .columns th.small-12{           display:block !important;           width:100% !important;      }}  @media only screen and (max-width:766px){       table.menu{         width:100% !important;      }}  @media only screen and (max-width:766px){       table.menu td,table.menu th{            width:auto !important;          display:inline-block !important;        }}  @media only screen and (max-width:766px){       table.menu.vertical td,table.menu.vertical th{          display:block !important;       }}  @media only screen and (max-width:766px){       table.menu[align=center]{           width:auto !important;      }}  @media only screen and (max-width:766px){       .footer table.footer__app.menu th.menu-item+th.menu-item a{         margin-left:0;      }}  @media only screen and (max-width: 480px){      table#canspamBar td{            font-size:14px !important;      }}  @media only screen and (max-width: 480px){      table#canspamBar td a{          display:block !important;           margin-top:10px !important;     }}</style></head>  <body style="-moz-box-sizing:border-box;-ms-text-size-adjust:100%;-webkit-box-sizing:border-box;-webkit-text-size-adjust:100%;Margin:0;background-color:#f1f1f1!important;box-sizing:border-box;color:#0a0a0a;font-family:Helvetica,Arial,sans-serif;font-size:16px;font-weight:400;line-height:1.3;margin:0;min-width:100%;padding:0;text-align:left;width:100%!important">    <span class="preheader" style="color:#f3f3f3;display:none!important;font-size:1px;line-height:1px;max-height:0;max-width:0;mso-hide:all!important;opacity:0;overflow:hidden;visibility:hidden"></span>    <table class="body" style="margin:0;background:#f3f3f3;background-color:#f1f1f1 !important;border-collapse:collapse;border-spacing:0;color:#0a0a0a;font-family:Helvetica, Arial, sans-serif;font-size:16px;font-weight:400;height:100%;line-height:1.3;padding:0;text-align:left;vertical-align:top;width:100%;">      <tr style="padding:0;text-align:left;vertical-align:top;">        <td class="center" align="center" valign="top" style="-webkit-hyphens:auto;margin:0;border-collapse:collapse !important;color:#0a0a0a;font-family:Helvetica, Arial, sans-serif;font-size:16px;font-weight:400;line-height:1.3;padding:0;text-align:left;vertical-align:top;word-wrap:break-word;">          <center style="min-width:750px;width:100%;">            <table align="center" class="wrapper float-center" style="margin:0 auto;border-collapse:collapse;border-spacing:0;float:none;padding:0;text-align:center;vertical-align:top;width:100%;">              <tr style="padding:0;text-align:left;vertical-align:top;">                <td class="wrapper-inner" style="-webkit-hyphens:auto;margin:0;border-collapse:collapse !important;color:#0a0a0a;font-family:Helvetica, Arial, sans-serif;font-size:16px;font-weight:400;line-height:1.3;padding:0;padding-bottom:70px;padding-top:72px;text-align:left;vertical-align:top;word-wrap:break-word;">                  <table align="center" class="container" style="margin:0 auto;background:#fefefe;border-collapse:collapse;border-spacing:0;padding:0;text-align:inherit;vertical-align:top;width:750px;">                    <tbody>                      <tr style="padding:0;text-align:left;vertical-align:top;">                        <td style="-webkit-hyphens:auto;margin:0;border-collapse:collapse !important;color:#0a0a0a;font-family:Helvetica, Arial, sans-serif;font-size:16px;font-weight:400;line-height:1.3;padding:0;text-align:left;vertical-align:top;word-wrap:break-word;">                          <table class="row header" style="background-color:#fff;border-bottom:1px solid #e1e1e1;border-collapse:collapse;border-spacing:0;color:#000;display:table;font-family:'PT Sans', sans-serif;font-size:13px;padding:0;position:relative;text-align:left;vertical-align:top;width:100%;">                            <tbody>                              <tr style="padding:0;text-align:left;vertical-align:top;">                                <th class="small-12 large-6 columns first" style="margin:0 auto;color:#0a0a0a;font-family:Helvetica, Arial, sans-serif;font-size:16px;font-weight:400;line-height:1.3;padding:0;padding-bottom:16px;padding-left:48px;padding-right:8px;padding-top:38px;text-align:left;width:359px;">                                  <table style="border-collapse:collapse;border-spacing:0;padding:0;text-align:left;vertical-align:top;width:100%;">                                    <tr style="padding:0;text-align:left;vertical-align:top;">                                      <th style="margin:0;color:#0a0a0a;font-family:Helvetica, Arial, sans-serif;font-size:16px;font-weight:400;line-height:1.3;padding:0;text-align:left;">                                        <a href="#" style="margin:0;color:#0074b8;font-family:Helvetica, Arial, sans-serif;font-weight:400;line-height:1.3;padding:0;text-align:left;text-decoration:none;"><img class="small-float-center" src="https://gallery.mailchimp.com/90cec03a3d306613bb7fd7476/images/bd5e9517-0057-490a-acf5-ede8ba35b27c.png" alt="VEKA" style="-ms-interpolation-mode:bicubic;border:none;clear:both;display:block;max-width:100%;outline:0;text-decoration:none;width:auto"></a>                                      </th>                                    </tr>                                  </table>                                </th>                                <th class="header__right small-12 large-6 columns last" style="margin:0 auto;color:#0a0a0a;font-family:Helvetica, Arial, sans-serif;font-size:16px;font-weight:400;line-height:1.3;padding:0;padding-bottom:16px;padding-left:8px;padding-right:48px;padding-top:38px;text-align:right;width:359px;">                                  <table style="border-collapse:collapse;border-spacing:0;padding:0;text-align:left;vertical-align:top;width:100%;">                                    <tr style="padding:0;text-align:left;vertical-align:top;">                                      <th style="margin:0;color:#0a0a0a;font-family:Helvetica, Arial, sans-serif;font-size:16px;font-weight:400;line-height:1.3;padding:0;text-align:left;">                                        <p class="text-right small-text-center header__title" style="margin:0;margin-bottom:10px;color:#0074b8;font-family:Helvetica, Arial, sans-serif;font-size:18px;font-weight:700;line-height:1.3;padding:0;padding-bottom:27px;text-align:right;">Надёжно. Навсегда.</p>                                        <p class="text-right small-text-center" style="margin:0;margin-bottom:10px;color:#0a0a0a;font-family:Helvetica, Arial, sans-serif;font-size:16px;font-weight:400;line-height:1.3;padding:0;text-align:right;"><a href="tel:88005058352" class="text-right small-text-center header__phone" style="margin:0;color:#000;display:inline-block;font-family:Helvetica, Arial, sans-serif;font-size:24px;font-weight:700;line-height:1.3;padding:0;text-align:left;text-decoration:none;vertical-align:top;">8 800 505-83-52</a>                                      </p>                                      <p class="text-right header__disc small-text-center" style="margin:0;margin-bottom:10px;color:#0a0a0a;font-family:Helvetica, Arial, sans-serif;font-size:16px;font-weight:400;line-height:1.3;padding:0;padding-bottom:35px;text-align:right;">Контакт-центр VEKA. Режим работы 24/7.<br>Звонок бесплатный.</p>                                    </th>                                  </tr>                                </table>                              </th>                            </tr>                          </tbody>                        </table>                        <table class="row content" style="border-collapse:collapse;border-spacing:0;display:table;padding:0;position:relative;text-align:left;vertical-align:top;width:100%;">                          <tbody>                            <tr style="padding:0;text-align:left;vertical-align:top;">                              <th class="small-12 large-12 columns first last" style="margin:0 auto;color:#0a0a0a;font-family:Helvetica, Arial, sans-serif;font-size:16px;font-weight:400;line-height:1.3;padding:0;padding-bottom:16px;padding-left:48px;padding-right:48px;text-align:left;width:734px;">                                <table style="border-collapse:collapse;border-spacing:0;padding:0;text-align:left;vertical-align:top;width:100%;">                                  <tr style="padding:0;text-align:left;vertical-align:top;">                                    <th style="margin:0;color:#0a0a0a;font-family:Helvetica, Arial, sans-serif;font-size:16px;font-weight:400;line-height:1.3;padding:0;text-align:left;">                                      <h1 class="content__title" style="margin:0;margin-bottom:0;color:inherit;font-family:Helvetica, Arial, sans-serif;font-size:30px;font-weight:700;line-height:1.3;padding:0;padding-bottom:35px;padding-top:35px;text-align:left;word-wrap:normal;">$arFields[NAME]</h1>                                      <p class="content__text" style="margin:0;margin-bottom:10px;color:#272727;font-family:Roboto, sans-serif;font-size:16px;font-weight:300;line-height:1.625;padding:0;text-align:left;">$arFields[PREVIEW_TEXT]</p>                                                                            <table class="spacer" style="border-collapse:collapse;border-spacing:0;padding:0;text-align:left;vertical-align:top;width:100%;">                                        <tbody>                                          <tr style="padding:0;text-align:left;vertical-align:top;">                                            <td height="50" style="-webkit-hyphens:auto;margin:0;border-collapse:collapse !important;color:#0a0a0a;font-family:Helvetica, Arial, sans-serif;font-size:50px;font-weight:400;line-height:50px;mso-line-height-rule:exactly;padding:0;text-align:left;vertical-align:top;word-wrap:break-word;"> </td>                                          </tr>                                        </tbody>                                      </table>                                    </th>                                  </tr>                                </table>                              </th>                            </tr>                          </tbody>                        </table>                        <table class="row content-footer" style="background-color:#0e72b0;border-collapse:collapse;border-spacing:0;display:table;padding:0;position:relative;text-align:left;vertical-align:top;width:100%;">                          <tbody>                            <tr style="padding:0;text-align:left;vertical-align:top;">                              <th class="small-12 large-6 columns first" style="margin:0 auto;color:#0a0a0a;font-family:Helvetica, Arial, sans-serif;font-size:16px;font-weight:400;line-height:1.3;padding:0;padding-bottom:35px;padding-left:48px;padding-right:8px;padding-top:35px;text-align:left;width:359px;">                                <table style="border-collapse:collapse;border-spacing:0;padding:0;text-align:left;vertical-align:top;width:100%;">                                  <tr style="padding:0;text-align:left;vertical-align:top;">                                    <th style="margin:0;color:#0a0a0a;font-family:Helvetica, Arial, sans-serif;font-size:16px;font-weight:400;line-height:1.3;padding:0;text-align:left;">                                      <p class="content-footer__left small-text-center" style="margin:0;margin-bottom:0;color:#fff;font-family:'PT Sans', sans-serif;font-size:14px;font-weight:400;line-height:1.71429;padding:0;text-align:left;"><b>Центральный завод и головной офис: </b>                                      <br><b>Тел./факс:</b> (495) 518 98 50, (495) 777 36 11<br><b>E-mail:</b> <a href="mailto:moscow@veka.com" target="_blank" style="color:#fff">moscow@veka.com</a></p>                                    </th>                                  </tr>                                </table>                              </th>                              <th class="small-12 large-6 columns last" style="margin:0 auto;color:#0a0a0a;font-family:Helvetica, Arial, sans-serif;font-size:16px;font-weight:400;line-height:1.3;padding:0;padding-bottom:35px;padding-left:8px;padding-right:48px;padding-top:35px;text-align:left;width:359px;">                                <table style="border-collapse:collapse;border-spacing:0;padding:0;text-align:left;vertical-align:top;width:100%;">                                  <tr style="padding:0;text-align:left;vertical-align:top;">                                    <th style="margin:0;color:#0a0a0a;font-family:Helvetica, Arial, sans-serif;font-size:16px;font-weight:400;line-height:1.3;padding:0;text-align:left;">                                      <p class="text-right content-footer__right small-text-center" style="margin:0;margin-bottom:0;color:#fff;font-family:'PT Sans', sans-serif;font-size:12px;font-weight:400;line-height:1.5;padding:0;padding-top:13px;text-align:right;"><b>© 2017 VEKA.</b>                                      <br>Вы зарегистриованы в партнерском разделе<br>на сайте <a style="color: #fff;" href="http://www.veka.ru/">veka.ru.</a> Вы подписаны на уведомления по почте.</p>                                    </th>                                  </tr>                                </table>                              </th>                            </tr>                          </tbody>                        </table>                      </td>                    </tr>                  </tbody>                </table>                <table align="center" class="container footer" style="margin:0 auto;background:0 0;border-collapse:collapse;border-spacing:0;padding:0;text-align:inherit;vertical-align:top;width:750px;">                  <tbody>                    <tr style="padding:0;text-align:left;vertical-align:top;">                      <td style="-webkit-hyphens:auto;margin:0;border-collapse:collapse !important;color:#0a0a0a;font-family:Helvetica, Arial, sans-serif;font-size:16px;font-weight:400;line-height:1.3;padding:0;text-align:left;vertical-align:top;word-wrap:break-word;">                        <table class="row" style="border-collapse:collapse;border-spacing:0;display:table;padding:0;position:relative;text-align:left;vertical-align:top;width:100%;">                          <tbody>                            <tr style="padding:0;text-align:left;vertical-align:top;">                              <th class="small-12 large-4 columns first" style="margin:0 auto;color:#0a0a0a;font-family:Helvetica, Arial, sans-serif;font-size:16px;font-weight:400;line-height:1.3;padding:0;padding-bottom:16px;padding-left:16px;padding-right:8px;text-align:left;width:234px;">                                <table style="border-collapse:collapse;border-spacing:0;padding:0;text-align:left;vertical-align:top;width:100%;">                                  <tr style="padding:0;text-align:left;vertical-align:top;">                                    <th style="margin:0;color:#0a0a0a;font-family:Helvetica, Arial, sans-serif;font-size:16px;font-weight:400;line-height:1.3;padding:0;text-align:left;">                                      <h2 class="footer__title" style="margin:0;margin-bottom:0;color:#0074b8;font-family:Helvetica, Arial, sans-serif;font-size:17px;font-weight:700;line-height:1.3;padding:0;padding-top:34px;text-align:left;word-wrap:normal;">Мы в социальных сетях</h2>                                      <table class="spacer" style="border-collapse:collapse;border-spacing:0;padding:0;text-align:left;vertical-align:top;width:100%;">                                        <tbody>                                          <tr style="padding:0;text-align:left;vertical-align:top;">                                            <td height="27" style="-webkit-hyphens:auto;margin:0;border-collapse:collapse !important;color:#0a0a0a;font-family:Helvetica, Arial, sans-serif;font-size:27px;font-weight:400;line-height:27px;mso-line-height-rule:exactly;padding:0;text-align:left;vertical-align:top;word-wrap:break-word;"> </td>                                          </tr>                                        </tbody>                                      </table>                                      <table class="menu footer__social" style="border-collapse:collapse;border-spacing:0;padding:0;text-align:left;vertical-align:top;width:100%;">                                        <tr style="padding:0;text-align:left;vertical-align:top;">                                          <td style="-webkit-hyphens:auto;margin:0;border-collapse:collapse !important;color:#0a0a0a;font-family:Helvetica, Arial, sans-serif;font-size:16px;font-weight:400;line-height:1.3;padding:0;text-align:left;vertical-align:top;word-wrap:break-word;">                                            <table style="border-collapse:collapse;border-spacing:0;padding:0;text-align:left;vertical-align:top;width:100%;">                                              <tr style="padding:0;text-align:left;vertical-align:top;">                                                <th class="menu-item float-center" style="margin:0 auto;color:#0a0a0a;float:none;font-family:Helvetica, Arial, sans-serif;font-size:16px;font-weight:400;line-height:1.3;padding:0;padding-right:10px;text-align:center;">                                                  <a href="#" style="margin:0;background-color:#fff;color:#0074b8;display:block;font-family:Helvetica, Arial, sans-serif;font-weight:400;height:36px;line-height:1.3;margin-right:7px;padding:0;padding-top:10px;text-align:left;text-decoration:none;vertical-align:middle;width:36px;"><img src="https://gallery.mailchimp.com/90cec03a3d306613bb7fd7476/images/570938d1-c356-4a56-8a36-6fd2d281ca84.png" alt="Вконтакте" style="-ms-interpolation-mode:bicubic;border:none;clear:both;display:block;margin:0 auto;max-width:100%;outline:0;text-decoration:none;width:auto"></a>                                                </th>                                                <th class="menu-item float-center" style="margin:0 auto;color:#0a0a0a;float:none;font-family:Helvetica, Arial, sans-serif;font-size:16px;font-weight:400;line-height:1.3;padding:0;padding-right:10px;text-align:center;">                                                  <a href="#" style="margin:0;background-color:#fff;color:#0074b8;display:block;font-family:Helvetica, Arial, sans-serif;font-weight:400;height:36px;line-height:1.3;margin-right:7px;padding:0;padding-top:10px;text-align:left;text-decoration:none;vertical-align:middle;width:36px;"><img src="https://gallery.mailchimp.com/90cec03a3d306613bb7fd7476/images/ae88e938-1931-4a8b-b941-860893cb529c.png" alt="Facebook" style="-ms-interpolation-mode:bicubic;border:none;clear:both;display:block;margin:0 auto;max-width:100%;outline:0;text-decoration:none;width:auto"></a>                                                </th>                                                <th class="menu-item float-center" style="margin:0 auto;color:#0a0a0a;float:none;font-family:Helvetica, Arial, sans-serif;font-size:16px;font-weight:400;line-height:1.3;padding:0;padding-right:10px;text-align:center;">                                                  <a href="#" style="margin:0;background-color:#fff;color:#0074b8;display:block;font-family:Helvetica, Arial, sans-serif;font-weight:400;height:36px;line-height:1.3;margin-right:7px;padding:0;padding-top:10px;text-align:left;text-decoration:none;vertical-align:middle;width:36px;"><img src="https://gallery.mailchimp.com/90cec03a3d306613bb7fd7476/images/6c79d6c2-0c29-430c-9f9c-ac5e70058b6e.png" alt="Twitter" style="-ms-interpolation-mode:bicubic;border:none;clear:both;display:block;margin:0 auto;max-width:100%;outline:0;text-decoration:none;width:auto"></a>                                                </th>                                                <th class="menu-item float-center" style="margin:0 auto;color:#0a0a0a;float:none;font-family:Helvetica, Arial, sans-serif;font-size:16px;font-weight:400;line-height:1.3;padding:0;padding-right:10px;text-align:center;">                                                  <a href="#" style="margin:0;background-color:#fff;color:#0074b8;display:block;font-family:Helvetica, Arial, sans-serif;font-weight:400;height:36px;line-height:1.3;margin-right:7px;padding:0;padding-top:10px;text-align:left;text-decoration:none;vertical-align:middle;width:36px;"><img src="https://gallery.mailchimp.com/90cec03a3d306613bb7fd7476/images/f50b574f-816a-4ca0-9922-c3ced7f80cda.png" alt="YouTube" style="-ms-interpolation-mode:bicubic;border:none;clear:both;display:block;margin:0 auto;max-width:100%;outline:0;text-decoration:none;width:auto"></a>                                                </th>                                                <th class="menu-item float-center" style="margin:0 auto;color:#0a0a0a;float:none;font-family:Helvetica, Arial, sans-serif;font-size:16px;font-weight:400;line-height:1.3;padding:0;padding-right:10px;text-align:center;">                                                  <a href="#" style="margin:0;background-color:#fff;color:#0074b8;display:block;font-family:Helvetica, Arial, sans-serif;font-weight:400;height:36px;line-height:1.3;margin-right:7px;padding:0;padding-top:10px;text-align:left;text-decoration:none;vertical-align:middle;width:36px;"><img src="https://gallery.mailchimp.com/90cec03a3d306613bb7fd7476/images/437057cb-5a6d-4a8c-8b4a-1af8befa7681.png" alt="Instagram" style="-ms-interpolation-mode:bicubic;border:none;clear:both;display:block;margin:0 auto;max-width:100%;outline:0;text-decoration:none;width:auto"></a>                                                </th>                                                <th class="menu-item float-center" style="margin:0 auto;color:#0a0a0a;float:none;font-family:Helvetica, Arial, sans-serif;font-size:16px;font-weight:400;line-height:1.3;padding:0;padding-right:10px;text-align:center;">                                                  <a href="#" style="margin:0;background-color:#fff;color:#0074b8;display:block;font-family:Helvetica, Arial, sans-serif;font-weight:400;height:36px;line-height:1.3;margin-right:7px;padding:0;padding-top:10px;text-align:left;text-decoration:none;vertical-align:middle;width:36px;"><img src="https://gallery.mailchimp.com/90cec03a3d306613bb7fd7476/images/45849e30-b23e-4abe-9091-751a9cf311e7.png" alt="Viber" style="-ms-interpolation-mode:bicubic;border:none;clear:both;display:block;margin:0 auto;max-width:100%;outline:0;text-decoration:none;width:auto"></a>                                                </th>                                              </tr>                                            </table>                                          </td>                                        </tr>                                      </table>                                    </th>                                  </tr>                                </table>                              </th>                              <th class="small-12 large-8 columns last" style="margin:0 auto;color:#0a0a0a;font-family:Helvetica, Arial, sans-serif;font-size:16px;font-weight:400;line-height:1.3;padding:0;padding-bottom:16px;padding-left:8px;padding-right:16px;text-align:left;width:484px;">                                <table style="border-collapse:collapse;border-spacing:0;padding:0;text-align:left;vertical-align:top;width:100%;">                                  <tr style="padding:0;text-align:left;vertical-align:top;">                                    <th style="margin:0;color:#0a0a0a;font-family:Helvetica, Arial, sans-serif;font-size:16px;font-weight:400;line-height:1.3;padding:0;text-align:left;">                                      <h2 class="footer__title" style="margin:0;margin-bottom:0;color:#0074b8;font-family:Helvetica, Arial, sans-serif;font-size:17px;font-weight:700;line-height:1.3;padding:0;padding-top:34px;text-align:left;word-wrap:normal;">Наши приложения</h2>                                      <table class="spacer" style="border-collapse:collapse;border-spacing:0;padding:0;text-align:left;vertical-align:top;width:100%;">                                        <tbody>                                          <tr style="padding:0;text-align:left;vertical-align:top;">                                            <td height="15" style="-webkit-hyphens:auto;margin:0;border-collapse:collapse !important;color:#0a0a0a;font-family:Helvetica, Arial, sans-serif;font-size:15px;font-weight:400;line-height:15px;mso-line-height-rule:exactly;padding:0;text-align:left;vertical-align:top;word-wrap:break-word;"> </td>                                          </tr>                                        </tbody>                                      </table>                                      <table class="menu footer__app" style="border-collapse:collapse;border-spacing:0;padding:0;text-align:left;vertical-align:top;width:100%;">                                        <tr style="padding:0;text-align:left;vertical-align:top;">                                          <td style="-webkit-hyphens:auto;margin:0;border-collapse:collapse !important;color:#0a0a0a;font-family:Helvetica, Arial, sans-serif;font-size:16px;font-weight:400;line-height:1.3;padding:0;text-align:left;vertical-align:top;word-wrap:break-word;">                                            <table style="border-collapse:collapse;border-spacing:0;padding:0;text-align:left;vertical-align:top;width:100%;">                                              <tr style="padding:0;text-align:left;vertical-align:top;">                                                <th class="menu-item float-center" style="margin:0 auto;color:#0a0a0a;float:none;font-family:Helvetica, Arial, sans-serif;font-size:16px;font-weight:400;line-height:1.3;padding:0;padding-bottom:10px;padding-right:10px;text-align:center;">                                                  <a href="https://play.google.com/store/apps/developer?id=VEKA+Rus" style="margin:0;background-color:#fff;color:#0074b8;display:block;font-family:Helvetica, Arial, sans-serif;font-weight:400;height:50px;line-height:1.3;min-width:210px;padding:0;padding-top:10px;text-align:left;text-decoration:none;"><img src="https://gallery.mailchimp.com/90cec03a3d306613bb7fd7476/images/8e670926-e549-4d50-9b35-701b76c7c01b.png" alt="Google Play" style="-ms-interpolation-mode:bicubic;border:none;clear:both;display:block;margin:0 auto;max-width:100%;outline:0;text-decoration:none;width:auto"></a>                                                </th>                                                <th class="menu-item float-center" style="margin:0 auto;color:#0a0a0a;float:none;font-family:Helvetica, Arial, sans-serif;font-size:16px;font-weight:400;line-height:1.3;padding:0;padding-bottom:10px;padding-right:10px;text-align:center;">                                                  <a href="https://itunes.apple.com/ru/developer/veka-rus/id585151342" style="margin:0;background-color:#fff;color:#0074b8;display:block;font-family:Helvetica, Arial, sans-serif;font-weight:400;height:50px;line-height:1.3;margin-left:10px;min-width:210px;padding:0;padding-top:10px;text-align:left;text-decoration:none;"><img src="https://gallery.mailchimp.com/90cec03a3d306613bb7fd7476/images/6135ed74-3b87-4847-b6b1-a4e1911a2641.png" alt="App Store" style="-ms-interpolation-mode:bicubic;border:none;clear:both;display:block;margin:0 auto;max-width:100%;outline:0;text-decoration:none;width:auto"></a>                                                </th>                                              </tr>                                            </table>                                          </td>                                        </tr>                                      </table>                                    </th>                                  </tr>                                </table>                              </th>                            </tr>                          </tbody>                        </table>                      </td>                    </tr>                  </tbody>                </table>              </td>            </tr>          </table>        </center>      </td>    </tr>  </table></body></html>
EOT;
 
        $key        = "9f9971ed2bb18bd01042456abd294862-us16";
        $dc         = "us16";
        $listId     = "0196c89a5d";
        $plain      = $arFields["NAME"];
        $res = new IldarMailchimp($key, $dc);
        $newCompaty = $res -> createCamping($listId, $arFields["NAME"], 'Veka', 'latishew@gmail.com');
         
        // Получим id новой компании
        $camp_id = $newCompaty->id;

        // Добавлем к ней контент
        $content = $res -> createCampingContent($plain, $html, $camp_id);        

        $target = "campaigns/" . $camp_id . "/actions/send";
        //старт рассылки
        $send = $res -> mc_request($key, $target);

                unset($arFields["PROPERTY_VALUES"][267][0]["VALUE"]);

            }
                //$file = $_SERVER["DOCUMENT_ROOT"]."/local/php_interface/include/event_handlers/mailchimp/log.txt";
                //$string = "<?php\n return ".var_export($arFields, true).';';
                //file_put_contents($file, $string);
        }

    }
}

AddEventHandler("subscribe", "OnStartSubscriptionAdd", Array("MyClassAddMailchimpSubscriber", "OnStartSubscriptionAddHandler"));

class MyClassAddMailchimpSubscriber
{
   
    function OnStartSubscriptionAddHandler($arFields)
    {       
        $key        = "9f9971ed2bb18bd01042456abd294862-us16";
        $dc         = "us16";       
        $ress = new IldarMailchimp($key, $dc);
        $subscribe = $ress->addSubscriber("0196c89a5d", $arFields["EMAIL"]);
    }
} 

?>