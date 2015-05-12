<?php
/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) 2000-2015 LOCKON CO.,LTD. All Rights Reserved.
 *
 * http://www.lockon.co.jp/
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */


namespace Eccube\Repository;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Finder\Finder;

/**
 * PageLayoutRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class PageLayoutRepository extends EntityRepository
{
    public function setApp($app)
    {
        $this->app = $app;
    }

    public function get($deviceTypeId, $pageId)
    {
        $qb = $this->createQueryBuilder('p')
            ->select('p, bp, b')
            ->leftJoin('p.BlocPositions', 'bp', 'WITH', 'p.page_id = bp.page_id OR bp.anywhere = 1')
            ->innerJoin('bp.Bloc', 'b')
            ->andWhere('p.device_type_id = :deviceTypeId AND p.page_id = :pageId')
            ->addOrderBy('bp.target_id', 'ASC')
            ->addOrderBy('bp.bloc_row', 'ASC');

        return $qb
            ->getQuery()
            ->setParameters(array(
                'deviceTypeId'  => $deviceTypeId,
                'pageId'        => $pageId,
            ))
            ->getSingleResult();
    }

    public function getByUrl($deviceTypeId, $url)
    {
        $qb = $this->createQueryBuilder('p')
            ->select('p, bp, b')
            ->leftJoin('p.BlocPositions', 'bp', 'WITH', 'p.page_id = bp.page_id OR bp.anywhere = 1')
            ->innerJoin('bp.Bloc', 'b')
            ->andWhere('p.device_type_id = :deviceTypeId AND p.url = :url')
            ->addOrderBy('bp.target_id', 'ASC')
            ->addOrderBy('bp.bloc_row', 'ASC');

        return $qb
            ->getQuery()
            ->setParameters(array(
                'deviceTypeId'  => $deviceTypeId,
                'url'           => $url,
            ))
            ->getSingleResult();
    }

    public function getByRoutingName($deviceTypeId, $routingName)
    {
        $legacyUrls = array(
            'preview' => 'preview',
            'homepage' => 'index.php',

            'product_list' => 'products/list.php',
            'product_detail' => 'products/detail.php',

            'mypage' => 'mypage/index.php',
            'mypage_change' => 'mypage/change.php',
            'mypage_change_complete' => 'mypage/change_complete.php',
            'mypage_delivery' => 'mypage/delivery.php',
            'mypage_favorite' => 'mypage/favorite.php',
            'mypage_history' => 'mypage/history.php',
            'mypage_login' => 'mypage/login.php',
            'mypage_refusal' => 'mypage/refusal.php',
            'mypage_refusal_complete' => 'mypage/refusal_complete.php',

            'help_about' => 'abouts/index.php',
            'cart' => 'cart/index.php',

            'contact' => 'contact/index.php',
            'contact_complete' => 'contact/complete.php',

            'entry' => 'entry/index.php',
//            'entry_kiyaku' => 'entry/kiyaku.php',
            'entry_complete' => 'entry/complete.php',

            'help_tradelaw' => 'order/index.php',
            'regist_complete' => 'regist/complete.php',

            'shopping' => 'shopping/index.php',
            'shopping_delivery' => 'shopping/deliv.php',
            'shopping_shipping_multiple' => 'shopping/multiple.php',
            'shopping_payment' => 'shopping/payment.php',
            'shopping_confirm' => 'shopping/confirm.php',
            'shopping_complete' => 'shopping/complete.php',

            'help_privacy' => 'guide/privacy.php',
        );

        if (!array_key_exists($routingName, $legacyUrls)) {
            throw new \Doctrine\ORM\NoResultException();
        }

        return $this->getByUrl($deviceTypeId, $legacyUrls[$routingName]);
    }

    public function newPageLayout($deviceTypeId)
    {
        $PageLayout = new \Eccube\Entity\PageLayout();
        $PageLayout
            ->setDeviceTypeId($deviceTypeId);
        $page_id = $this->getNewPageId($deviceTypeId);
        $PageLayout->setPageId($page_id);

        return $PageLayout;
    }

    public function findOrCreate($page_id, $deviceTypeId)
    {

        if ($page_id == null) {
            return $this->newPageLayout($deviceTypeId);
        } else {
            return $this->getPageProperties($page_id, $deviceTypeId);
        }

    }

    private function getNewPageId($deviceTypeId)
    {
        $qb = $this->createQueryBuilder('l')
            ->select('max(l.page_id) +1 as page_id')
            ->where('l.device_type_id = :device_type_id')
            ->setParameter('device_type_id', $deviceTypeId);
        $result = $qb->getQuery()->getSingleResult();

        return $result['page_id'];
    }

    /**
     * ページの属性を取得する.
     *
     * この関数は, dtb_pagelayout の情報を検索する.
     * $deviceTypeId は必須. デフォルト値は DEVICE_TYPE_PC.
     *
     * @access public
     * @param  integer  $deviceTypeId 端末種別ID
     * @param  string   $where          追加の検索条件
     * @param  string[] $parameters     追加の検索パラメーター
     * @return array    ページ属性の配列
     */
    public function getPageList($deviceTypeId, $where = '', $parameters = array())
    {

        $qb = $this->createQueryBuilder('l')
            ->orderBy('l.page_id', 'DESC')
            ->where('l.device_type_id = :device_type_id')
            ->setParameter('device_type_id', $deviceTypeId)
            ->andWhere('l.page_id <> 0');
        if ($where != '') {
            $qb->andWhere($where);
            foreach ($parameters as $key => $val) {
                $qb->setParameter($key, $val);
            }
        }

        $PageLayouts = $qb
            ->getQuery()
            ->getResult();

        return $PageLayouts;
    }

    public function getPageProperties($page_id, $deviceTypeId, $where = '', $parameters = array())
    {
        $qb = $this->createQueryBuilder('l')
            ->orderBy('l.page_id', 'DESC')
            ->where('l.page_id = :page_id')
            ->setParameter('page_id', $page_id)
            ->andWhere('l.device_type_id = :device_type_id')
            ->setParameter('device_type_id', $deviceTypeId);

        if ($where != '') {
            $qb->andWhere($where);
            foreach ($parameters as $key => $val) {
                $qb->setParameter($key, $val);
            }
        }

        $PageLayout = $qb
            ->getQuery()
            ->getSingleResult();

        return $PageLayout;
    }

    /**
     * テンプレートのパスを取得する.
     *
     * @access public
     * @param  integer $deviceTypeId 端末種別ID
     * @param  boolean $isUser         USER_REALDIR 以下のパスを返す場合 true
     * @return string  テンプレートのパス
     */
    public function getTemplatePath($deviceTypeId, $isUser = false)
    {
        $app = $this->app;
        $templateName = '';
        switch ($deviceTypeId) {
            case $app['config']['device_type_mobile']:
                $dir = $app['config']['mobile_template_realdir'];
                $templateName =  $app['config']['mobile_template_name'];
                break;

            case $app['config']['device_type_smartphone']:
                $dir = $app['config']['smartphone_template_realdir'];
                $templateName =  $app['config']['smartphone_template_name'];
                break;

            case $app['config']['device_type_pc'];
                $dir = $app['config']['template_realdir'];
                $templateName =  $app['config']['template_name'];
                break;
        }
        $userPath = $app['config']['user_realdir'];
        if ($isUser) {
            $dir = $userPath . $app['config']['user_package_dir'] . $templateName . '/';
        }

        return $dir;
    }

    /**
     * ページデータを取得する.
     * @param  integer $filename       ファイル名
     * @param  integer $deviceTypeId 端末種別ID
     * @param  boolean $isUser
     * @return mixed
     */
    public function getTemplateFile($filename, $deviceTypeId, $isUser = false)
    {
        $templatePath = $this->getTemplatePath($deviceTypeId, $isUser);

        $finder = Finder::create();
        $finder->followLinks();
        // TODO: ファイル名にディレクトリのパスが一部含まれるので/ディレクトと分ける処理。イケてない・・・
        $arrDir = explode('/', $filename);
        for ($index =0; $index < count($arrDir)-1; $index++) {
            $templatePath .= $arrDir[$index] . '/';
        }
        // TODO: .tpl, .twig が混在するためひとまず*。元の$filenameから拡張子込で持ちたい。
        $finder->in($templatePath)->name($arrDir[$index].'.*');

        $data = null;
        if ($finder->count() === 1) {
            foreach ($finder as $file) {
                $data = array(
                    'file_name' => $file->getFileName(),
                    'tpl_data' => file_get_contents($file->getPathName())
                );
            }
        }

        return $data;
    }
}
