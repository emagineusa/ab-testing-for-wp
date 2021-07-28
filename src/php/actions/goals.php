<?php

namespace ABTestingForWP;

class GoalActions {

    private function addTypeToList($input, $type, $strings) {
        array_push($input, array_merge(['name' => $type->name, 'label' => $type->label], $strings));
        return $input;
    }

    public function getGoalTypes() {
        $postTypes = get_post_types(
            [
                'public' => true,
            ],
            'objects'
        );

        $strings = apply_filters('ab-testing-for-wp_goal-type-strings', [
            'post' => [
                'itemName' => __('Post', 'ab-testing-for-wp'),
                'help' => __('Goal post for this test. If the visitor lands on this post it will add a point to the tested variant.', 'ab-testing-for-wp'),
            ],
            'page' => [
                'itemName' => __('Page', 'ab-testing-for-wp'),
                'help' => __('Goal page for this test. If the visitor lands on this page it will add a point to the tested variant.', 'ab-testing-for-wp'),
            ],
        ]);

        // Array of post type slugs to exclude from from the Goal dropdown in block settings.
        $postTypesToExclude = apply_filters('ab-testing-for-wp_post-types-to-exclude', ['attachment', 'abt4wp-test']);

        // Loop over all public post types and add them to the Goal List.
        $allowedGoalTypes = [];
        foreach ($postTypes as $slug => $postType) {
            // Skip excluded post types.
            if (in_array($slug, $postTypesToExclude)) {
                continue;
            }

            array_push($allowedGoalTypes, [
                'name' => $slug,
                'label' => $postType->label,
                'itemName' => ! empty( $strings[ $slug ]['itemName'] ) ? $strings[ $slug ]['itemName'] : $postType->label,
                'help' => ! empty( $strings[ $slug ]['help'] ) ? $strings[ $slug ]['help'] : $strings['post']['help'],
            ]);
        }

        array_push($allowedGoalTypes, [
            'name' => 'outbound',
            'label' => __('Outbound link', 'ab-testing-for-wp'),
            'itemName' => __('Visitor goes to', 'ab-testing-for-wp'),
            'help' => __('If visitor goes to this link, it will add a point for the tested variant.', 'ab-testing-for-wp'),
            'placeholder' => __('https://', 'ab-testing-for-wp'),
            'text' => true,
        ]);

        $allowedGoalTypes = apply_filters('ab-testing-for-wp_goal-types', $allowedGoalTypes);

        return rest_ensure_response($allowedGoalTypes);
    }

}
