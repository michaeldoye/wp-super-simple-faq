var app = angular.module("wp_super_simple_faq", ['ngAnimate']);

app

  .filter('to_trusted', ['$sce', function ($sce){
    return function(text) {
      return $sce.trustAsHtml(text);
    };
  }])

  .factory('DataLoader', function($http) {

    return {
      getFaqTopics: function() {
        return $http.get(faq.ajax_url+'?action=ssf_ajax_get_topics');
      },
      getFaqChildTopics: function(parent) {
        return $http.get(faq.ajax_url+'?action=ssf_ajax_get_child_topics&parent='+parent);
      },
      getFaqTopicsContent: function(postId) {
        return $http.get(faq.ajax_url+'?action=ssf_ajax_get_topics_content&id='+postId);
      }        
    } 

  })

  .controller("faqCtrl", function($scope, $http, DataLoader) {

    $scope.parentTopic = 'undefined';

    DataLoader.getFaqTopics().then(function(response) {
      //console.log(response.data);
      $scope.faqTopics = response.data;
    });

    $scope.getChildTopics = function(parent) {

      $scope.childTopic  = 'undefined';

      DataLoader.getFaqChildTopics(parent).then(function(response) {
        $scope.childTopics = response.data;
      });

    }

    $scope.getTopicContent = function(postId) {

      DataLoader.getFaqTopicsContent(postId).then(function(response) {
        console.log(response.data);
        $scope.postObject = response.data;
      });

    }

  })



//jQuery if required
jQuery(document).ready(function($) {});