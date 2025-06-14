<?php

namespace WooCommerce\InstallmentPurchase\Core;

class Loader {
    protected $actions;
    protected $filters;
    protected $shortcodes;

    public function __construct() {
        $this->actions = array();
        $this->filters = array();
        $this->shortcodes = array();
    }

    public function add_action($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->actions = $this->add($this->actions, $hook, $component, $callback, $priority, $accepted_args);
    }

    public function add_filter($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->filters = $this->add($this->filters, $hook, $component, $callback, $priority, $accepted_args);
    }

    public function add_shortcode($tag, $component, $callback) {
        $this->shortcodes = $this->add($this->shortcodes, $tag, $component, $callback, 0, 0);
    }

    private function add($hooks, $hook, $component, $callback, $priority, $accepted_args) {
        $hooks[] = array(
            'hook'          => $hook,
            'component'     => $component,
            'callback'      => $callback,
            'priority'      => $priority,
            'accepted_args' => $accepted_args
        );

        return $hooks;
    }

    public function run() {
        foreach ($this->filters as $hook) {
            if (is_string($hook['component']) && is_string($hook['callback'])) {
                // Static method callback as 'ClassName::methodName'
                add_filter(
                    $hook['hook'],
                    $hook['component'] . '::' . $hook['callback'],
                    $hook['priority'],
                    $hook['accepted_args']
                );
            } else {
                // Object method callback
                add_filter(
                    $hook['hook'],
                    array($hook['component'], $hook['callback']),
                    $hook['priority'],
                    $hook['accepted_args']
                );
            }
        }

        foreach ($this->actions as $hook) {
            if (is_string($hook['component']) && is_string($hook['callback'])) {
                // Static method callback as 'ClassName::methodName'
                add_action(
                    $hook['hook'],
                    $hook['component'] . '::' . $hook['callback'],
                    $hook['priority'],
                    $hook['accepted_args']
                );
            } else {
                // Object method callback
                add_action(
                    $hook['hook'],
                    array($hook['component'], $hook['callback']),
                    $hook['priority'],
                    $hook['accepted_args']
                );
            }
        }

        foreach ($this->shortcodes as $hook) {
            add_shortcode(
                $hook['hook'],
                array($hook['component'], $hook['callback'])
            );
        }
    }
} 