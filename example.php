<?php

require_once 'LevDBreadcrumbsData.php';

function show_custom_breadcrumbs($args = [])
{
    $crumbs_data = new LevDBreadcrumbsData();
    $crumbs = $crumbs_data->build($args);

    if ( ! empty($crumbs) ) {
        echo '<ul itemscope itemtype="https://schema.org/BreadcrumbList">';
        foreach ($crumbs as $i => $crumb) {
            if ( empty($crumb['url']) ) {
                echo '<li>';
                echo '<span class="current">';
                echo '<span>' . $crumb['title'] . '</span>';
                echo '</span>';
                echo '</li>';
            } else {
                echo '<li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">';
                echo '<a itemprop="item" href="' . $crumb['url'] . '">';
                echo '<span itemprop="name">' . $crumb['title'] . '</span>';
                echo '</a>';
                echo '<meta itemprop="position" content="' . ($i+1) . '" />';
                echo '</li>';
            }
        }
        echo '</ul>';
    }

}

show_custom_breadcrumbs();