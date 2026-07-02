<?php
    class IeProProfileObject {
        /**
         * @var ModelExtensionModuleIeProTabProfiles
         */
        protected $controller;

        protected $language;

        /**
         * @var ModelLoader
         */
        protected $model_loader;

        /**
         * @var HttpPostParameters
         */
        protected $parameters;

        /**
         * @var ProfileManager
         */
        protected $profile_manager;

        public function __construct( $controller) {
            $this->controller = $controller;
            $this->language = $this->controller->language;
            $this->model_loader = new ModelLoader( $controller);
            $this->parameters = HttpPostParameters::from( $controller->request);
            $this->profile_manager = new ProfileManager( $controller);
        }
    }
