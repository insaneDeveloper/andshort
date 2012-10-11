<?php
require_once('Abstract_Service.php');
/**
 *
 */
class TagController extends Abstract_Service {

    /**
     *
     * @var <type>
     */
    public $request;
    /**
     *
     * @var <type>
     */
    public $params;
    /**
     *
     * @var <type>
     */
    public $useDefaultService=true;
    /**
     *
     * @var <type> 
     */
    public $session;

    /**
     *
     */
    public function __construct() {
        $this->request=new Control_Request;
        $this->params = $this->request->getParams();
        $this->session = Control_SessionManager::getInstance();
    }
    /**
     *
     */
    public function defaultAction() {

        if(!isset($this->args['tag'])):

        else:

            $tagname = $this->args['tag'];

            $this->view=new View_Template("view/tag");

            Depends('View::TagCloudMaker');
            Depends('Models::Tag');
            Depends('Models::Url');

            $Tag      = new Tag;

            $ranks    = $Tag->getDefaultAdapter()->fetch(Sql_Logic::TOP10URL);
            $clouds   = $Tag->fetchRandomTags(30);

            $clouder  = new Visual_TagCloudMaker($clouds, array(
                'st-tags t1',
                'st-tags t2',
                'st-tags t3'
            ));

            $tagcloud = $clouder->tagcloud('getName');

            $this->view->assign('currentTag',$tagname);
            $this->view->loop('tagcloud', $tagcloud);
            $this->view->loop('ranking', $ranks);

        endif;

        $this->result = $this->view;
    }
}
?>