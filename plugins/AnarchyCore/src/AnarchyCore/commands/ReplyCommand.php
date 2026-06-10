<?php
declare(strict_types=1);

namespace AnarchyCore\commands;

use AnarchyCore\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class ReplyCommand extends Command {

    private Main $plugin;

    public function __construct(Main $plugin) {
        parent::__construct("reply", "Reply to the last private message", "/reply <message>", ["r"]);
        $this->plugin = $plugin;
        $this->setPermission("anarchycore.reply");
    }

    public function execute(CommandSender $sender, string $label, array $args): bool {
        if (!$sender instanceof Player) {
            $sender->sendMessage("§cIn-game only.");
            return true;
        }
        if (empty($args)) {
            $sender->sendMessage("§cUsage: §e/reply <message>");
            return true;
        }
        $pd = $this->plugin->getPlayerDataManager();
        $data = $pd->get($sender->getName());
        $lastSender = $data["last_msg_sender"] ?? null;

        if ($lastSender === null) {
            $sender->sendMessage("§cNo one to reply to.");
            return true;
        }

        $target = $this->plugin->getServer()->getPlayerByPrefix($lastSender);
        if ($target === null || !$target->isOnline()) {
            $sender->sendMessage("§c$lastSender is no longer online.");
            return true;
        }

        $message = implode(" ", $args);
        $target->sendMessage("§7[§d{$sender->getName()} §7→ §dYou§7] §f{$message}");
        $sender->sendMessage("§7[§dYou §7→ §d{$target->getName()}§7] §f{$message}");

        $tData = $pd->get($target->getName());
        $tData["last_msg_sender"] = $sender->getName();
        $pd->set($target->getName(), $tData);

        return true;
    }
}
