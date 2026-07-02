<?php
class ControllerDefault extends Controller {
    /**
     * Function to restrict direct access
     * 
     * @return string
     */
    public function index() {
        $this->load->language('extension/module/shipstation');
        echo $this->language->get('text_direct_access');
    }

}