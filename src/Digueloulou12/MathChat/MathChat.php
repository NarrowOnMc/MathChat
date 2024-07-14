<?php

namespace Digueloulou12\MathChat;

use pocketmine\command\ConsoleCommandSender;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;
use pocketmine\utils\Config;

class MathChat extends PluginBase implements Listener
{
    public static ?string $math = null;

    public function onEnable(): void
    {
        $configPath = $this->getDataFolder() . "config.yml";
        if (!file_exists($configPath)) {
            new Config($configPath, Config::YAML, [
                "time" => 300,
                "math" => [
                    "1+1" => 2,
                    "2+2" => 4
                ],
                "win_command" => "give {player} diamond",
                "win_message" => "The player {player} has found the answer to the calculation {math} which was {result}!",
                "message_server" => "The first player to find the {math} calculation wins 64 diamonds!"
            ]);
        }

        $this->getServer()->getPluginManager()->registerEvents($this, $this);

        $this->getScheduler()->scheduleRepeatingTask(new ClosureTask(
            function (): void {
                $mathQuestions = $this->getConfig()->get("math");
                $questions = array_keys($mathQuestions);
                $math = $questions[array_rand($questions)];
                MathChat::$math = $math;
                $message = str_replace("{math}", $math, $this->getConfig()->get("message_server"));
                Server::getInstance()->broadcastMessage($message);
            }
        ), 20 * $this->getConfig()->get("time"));
    }

    public function onChat(PlayerChatEvent $event): void
    {
        if (!is_null(self::$math)) {
            $message = $event->getMessage();
            $mathAnswers = $this->getConfig()->get("math");
            $player = $event->getPlayer();
            if ($message === strval($mathAnswers[self::$math])) {
                $winMessage = str_replace(
                    ["{math}", "{result}", "{player}"],
                    [self::$math, $mathAnswers[self::$math], $player->getName()],
                    $this->getConfig()->get("win_message")
                );
                $this->getServer()->broadcastMessage($winMessage);

                $winCommand = str_replace("{player}", $player->getName(), $this->getConfig()->get("win_command"));
                $this->getServer()->dispatchCommand(new ConsoleCommandSender($this->getServer(), $this->getServer()->getLanguage()), $winCommand);

                self::$math = null;
            }
        }
    }
}
