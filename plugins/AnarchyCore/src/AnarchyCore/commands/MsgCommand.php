<?php
declare(strict_types=1);

namespace AnarchyCore\commands;

use AnarchyCore\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class MsgCommand extends Command {

    private Main $plugin;

    public function __construct(Main $plugin) {
        parent::__construct("msg", "Send a private message", "/msg <player> <message>", ["m", "pm", "tell", "whisper", "w"]);
        $this->plugin = $plugin;
        $this->setPermission("anarchycore.msg");
    }

    public function execute(CommandSender $sender, string $label, array $args): bool {
        if (count($args) < 2) {
            $sender->sendMessage("§cUsage: §e/msg <player> <message>");
            return true;
        }
        $targetName = $args[0];
        $target = $this->plugin->getServer()->getPlayerByPrefix($targetName);
        if ($target === null || !$target->isOnline()) {
            $sender->sendMessage("§cPlayer '§e$targetName§c' is not online.");
            return true;
        }

        // Check if target has msg disabled
        $pd = $this->plugin->getPlayerDataManager();
        $data = $pd->get($target->getName());
        if (!($data["settings"]["msg_toggle"] ?? true)) {
            $sender->sendMessage("§c{$target->getName()} has private messages disabled.");
            return true;
        }

        $message = implode(" ", array_slice($args, 1));
        $senderName = $sender instanceof Player ? $sender->getName() : "Console";

        $target->sendMessage("§7[§d{$senderName} §7→ §dYou§7] §f{$message}");
        $sender->sendMessage("§7[§dYou §7→ §d{$target->getName()}§7] §f{$message}");

        // Set last msg sender for /reply
        if ($sender instanceof Player) {
            $sData = $pd->get($senderName);
            $sData["last_msg_sender"] = $target->getName();
            $pd->set($senderName, $sData);
        }
        $tData = $pd->get($target->getName());
        $tData["last_msg_sender"] = $senderName;
        $pd->set($target->getName(), $tData);

        return true;
    }
}
