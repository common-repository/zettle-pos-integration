<?php

declare(strict_types=1);

namespace Inpsyde\Zettle\ProductSettings\Taxonomy;

class ZettleSyncVisibilityTaxonomy
{
    /**
     * @var string
     */
    private $key;

    /**
     * @var string
     */
    private $attachedPostType;

    public function __construct(string $key, string $attachedPostType)
    {
        $this->key = $key;
        $this->attachedPostType = $attachedPostType;
    }

    /**
     * @return string
     */
    public function key(): string
    {
        return $this->key;
    }

    /**
     * @return string
     */
    public function attachedPostType(): string
    {
        return $this->attachedPostType;
    }

    public function create()
    {
        $labels = [
            'name' => _x('PayPal Zettle Sync Visibilities', 'zettle-pos-integration'),
            'singular_name' => _x('PayPal Zettle Sync Visibility', 'zettle-pos-integration'),
            'search_items' => __('Search PayPal Zettle Sync Visibilities', 'zettle-pos-integration'),
            'all_items' => __('All PayPal Zettle Sync Visibilities', 'zettle-pos-integration'),
            'parent_item' => __('Parent PayPal Zettle Sync Visibility', 'zettle-pos-integration'),
            'parent_item_colon' => __('Parent PayPal Zettle Sync Visibility:', 'zettle-pos-integration'),
            'edit_item' => __('Edit PayPal Zettle Sync Visibility', 'zettle-pos-integration'),
            'update_item' => __('Update PayPal Zettle Sync Visibility', 'zettle-pos-integration'),
            'add_new_item' => __('Add New PayPal Zettle Sync Visibility', 'zettle-pos-integration'),
            'new_item_name' => __('New PayPal Zettle Sync Visibility Name', 'zettle-pos-integration'),
            'menu_name' => __('PayPal Zettle Sync Visibilities', 'zettle-pos-integration'),
        ];

        $args = [
            'hierarchical' => false,
            'labels' => $labels,
            'public' => false,
            'show_ui' => false,
            'show_admin_column' => false,
            'show_in_nav_menus' => false,
            'show_tagcloud' => false,
            'show_in_rest' => false,
            'query_var' => true,
            'rewrite' => [
                'slug' => 'topic',
            ],
        ];

        register_taxonomy(
            $this->key(),
            [$this->attachedPostType()],
            $args
        );

        register_taxonomy_for_object_type(
            $this->key(),
            $this->attachedPostType()
        );
    }
}
