<?php
/**
 * Pipe-related classes.
 *
 * @see https://contactform7.com/selectable-recipient-with-pipes/
 */

/**
 * Class representing a pair of pipe.
 */
class WPCF7_Pipe {
    public ?string $before = '';

    public ?string $after = '';

    public function __construct($text) {
        $text = (string) $text;

        $pipe_pos = strpos($text, '|');

        if (false === $pipe_pos) {
            $this->before = $this->after = trim($text);
        } else {
            $this->before = trim(substr($text, 0, $pipe_pos));
            $this->after = trim(substr($text, $pipe_pos + 1));
        }
    }
}

/**
 * Class representing a list of pipes.
 */
class WPCF7_Pipes {
    /**
     * @var \WPCF7_Pipe[]
     */
    private array $pipes = [];

    public function __construct(array $texts) {
        foreach ($texts as $text) {
            $this->add_pipe($text);
        }
    }

    public function do_pipe(string $input): ?string {
        $input_canonical = wpcf7_canonicalize($input, [
            'strto' => 'as-is',
        ]);

        foreach ($this->pipes as $pipe) {
            $before_canonical = wpcf7_canonicalize($pipe->before, [
                'strto' => 'as-is',
            ]);

            if ($input_canonical === $before_canonical) {
                return $pipe->after;
            }
        }

        return $input;
    }

    public function collect_befores(): array {
        $befores = [];

        foreach ($this->pipes as $pipe) {
            $befores[] = $pipe->before;
        }

        return $befores;
    }

    public function collect_afters(): array {
        $afters = [];

        foreach ($this->pipes as $pipe) {
            $afters[] = $pipe->after;
        }

        return $afters;
    }

    public function zero(): bool {
        return empty($this->pipes);
    }

    public function random_pipe(): ?WPCF7_Pipe {
        if ($this->zero()) {
            return null;
        }

        return $this->pipes[array_rand($this->pipes)];
    }

    public function to_array(): array {
        return array_map(
            function (WPCF7_Pipe $pipe) {
                return [
                    $pipe->before,
                    $pipe->after,
                ];
            },
            $this->pipes
        );
    }

    private function add_pipe($text): void {
        $pipe = new WPCF7_Pipe($text);
        $this->pipes[] = $pipe;
    }
}
