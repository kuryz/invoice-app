<?php

namespace App\Core;

class View
{
    protected string $layout = 'layouts/main';
    protected array $sections = [];
    protected array $sectionStack = [];
    protected array $data = [];
    protected bool $isAdmin = false;
    protected string $parentLayout = '';

    public function __construct()
    {
        $this->sections = [];
        $this->sectionStack = [];
        $this->data = [];
    }

    
    public function render(string $view, array $data = []): string
    {
        $this->isAdmin = str_starts_with(ltrim($view, '/'), 'admin/');
        $data['session'] = session();
        // print_r($_SESSION['admin_id']);die;
        $this->data = array_merge($this->data, $data);

        // if ($this->isAdmin) {
        //     if (isset($_SESSION['admin_id'])) {
        //         // print_r($_SESSION['admin_id']);die;
        //         $admin = \App\Models\User::find($_SESSION['admin_id']);
        //         $this->data = array_merge(
        //             $this->data,
        //             ['admin' => $admin]
        //         );
        //     }
        //     $this->data = array_merge(
        //         $this->data,
        //         \App\Services\AdminViewComposerService::compose()
        //     );
        // }
        // print_r($view);die;

        return $this->renderView($view);
    }

    protected function renderView(string $view): string
    {
        $viewFile = $this->getViewFile($view);

        if (!file_exists($viewFile)) {
            throw new \Exception("View file not found: {$viewFile}");
        }

        extract($this->data);
        // Reset for every render
        $this->layout = '';
        $this->parentLayout = '';

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        if ($this->layout) {
            return $this->renderLayout($this->layout, $content);
        }

        return $content;
    }

    protected function renderLayout(string $layout, string $content): string
    {
        $layoutFile = $this->getViewFile($layout);
        

        if (!file_exists($layoutFile)) {
            throw new \Exception("Layout file not found: {$layoutFile}");
        }


        $yield = $content;
        extract($this->data);
        // print_r($yield);

        // reset before executing this layout
        $this->parentLayout = '';

        ob_start();
        require $layoutFile;
        $html = ob_get_clean();

        // save what the layout requested
        $parent = $this->parentLayout;

        if (!empty($parent)) {
            return $this->renderLayout($parent, $html);
        }

        return $html;
    }

    public function extends(string $layout): void
    {
        $this->parentLayout = $layout;
    }

    public function setLayout(string $layout): self
    {
        $this->layout = $layout;
        return $this;
    }

    public function with(string $key, $value): self
    {
        $this->data[$key] = $value;
        return $this;
    }

    /**
     * Start or set a section
     * If $content is null, start output buffering for the section.
     * Otherwise, set the section content directly.
     */
    public function section(string $name, ?string $content = null): void
    {
        // print_r($name);die;
        if ($content === null) {
            ob_start();
            $this->sectionStack[] = $name;
        } else {
            $this->sections[$name] = $content;
        }
    }

    /**
     * End the current section and store its content
     */
    public function end(): void
    {
        if (!empty($this->sectionStack)) {
            $name = array_pop($this->sectionStack);
            $this->sections[$name] = ob_get_clean();
        }
    }

    public function yield(string $name): string
    {
        return $this->sections[$name] ?? '';
    }

    protected function getViewFile(string $view): string
    {
        $view = str_replace('.', '/', $view);
        $view = rtrim($view, '/');
        
        $b = BASE_PATH . "/app/Views/{$view}.php";
        // var_dump($b);
        return $b;
    }
}