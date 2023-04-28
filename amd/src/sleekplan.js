/* jshint ignore:start */
define(['jquery', 'core/log', 'core/config'], function ($, log, mdlcfg) {

    "use strict"; // jshint ;

    log.debug('Add sleekplan initialised');

    function init_sleekplan(){
      var urlRemote = mdlcfg.wwwroot + "/local/yorktasks/sleekplantoken.php"
      log.debug('Generating Sleekplan SSO token');
      $.post(urlRemote,
      {
      }, function(data, status){
          if (status == "success"){
              log.debug('Sleekplan token generated, adding Sleekplan');
              window.SLEEK_USER = {
                token: data,
              };
              var sleekplanid = $('#sleekplanid').val();
              var sleekplanhtml = "<script type='text/javascript'>window.$sleek=[];window.SLEEK_PRODUCT_ID=" + sleekplanid + ";(function(){d=document;s=d.createElement('script');s.src='https://client.sleekplan.com/sdk/e.js';s.async=1;d.getElementsByTagName('head')[0].appendChild(s);})();</script>";
              $("body").append(sleekplanhtml);
          }
      });
    }

    return {
        init: function () {
            init_sleekplan();
        }
    };

});
/* jshint ignore:end */
