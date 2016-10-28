<?php
/**
 * The template for displaying all pages.
 *
 * This is the template that displays all pages by default.
 * Please note that this is the WordPress construct of pages
 * and that other 'pages' on your WordPress site will use a
 * different template.
 *
 * @package flatsome
 */

get_header(); ?>

<div  class="page-wrapper">
<div class="row">
	
<div id="content" class="large-12 columns" role="main">

		<?php while ( have_posts() ) : the_post(); ?>

				<?php get_template_part( 'content', 'page' ); ?>

		<?php endwhile; // end of the loop. ?>

		<div class="row parent-topics">

			<select ng-model="parentTopic" ng-change="getChildTopics(parentTopic)">
				<option value="undefined">Choose a Category</option>
				<option ng-repeat="topic in faqTopics" ng-value="{{topic.term_id}}">{{topic.name}}</option>
			</select>

			<div class="topic-radio-group" ng-show="childTopics" class="animate-show" ng-cloak>
				<h4 class="topic-header">Pick a topic you want to see</h4>
				<label ng-repeat="topic in childTopics" class="topic-radio" ng-cloak>
					<input ng-model="childTopic" ng-show="childTopics" ng-click="getTopicContent(childTopic)" name="topicGroup" class="animate-show" ng-cloak type="radio" ng-value="{{topic.term_id}}">
					{{topic.name}}
				</label>
			</div>

			<input ng-show="postObject" class="topic-content animate-show" type="text" ng-model="search" placeholder="Narrow your search..." ng-cloak />	

			<div ng-repeat="post in postObject | filter: search" ng-show="postObject" class="topic-content animate-show" ng-cloak>		
				<h4 class="faq-title" ng-click="showContent = !showContent">{{post.post_title}}</h4>
				<p ng-show="showContent" class="animate-show" ng-bind-html="post.post_content | to_trusted"></p>
			</div>			

		</div>

</div><!-- #content -->

</div><!-- .row -->
</div><!-- .page-wrapper -->

<?php get_footer(); ?>