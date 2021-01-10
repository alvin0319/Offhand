<?php

/*
 *       _       _        ___ _____ _  ___
 *   __ _| |_   _(_)_ __  / _ \___ // |/ _ \
 * / _` | \ \ / / | '_ \| | | ||_ \| | (_) |
 * | (_| | |\ V /| | | | | |_| |__) | |\__, |
 *  \__,_|_| \_/ |_|_| |_|\___/____/|_|  /_/
 *
 * Copyright (C) 2020 - 2021 alvin0319
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace alvin0319\OffHand;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\Item;
use pocketmine\nbt\tag\ListTag;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\MobEquipmentPacket;
use pocketmine\network\mcpe\protocol\types\ContainerIds;
use pocketmine\entity\Human;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;

use function array_merge;
use function array_filter;

class OffHand extends PluginBase implements Listener{

	public function onEnable() : void{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}
	/**
	 * @priority MONITOR
	 */
	public function onPlayerJoin(PlayerJoinEvent $event) : void{
        	$player = $event->getPlayer();
        	$this->getOffhandInventory($player)->sendContents($player);
	}

	/**
	 * @ignoreCancelled true
	 * @priority MONITOR
	 */
	public function onDataPacketReceive(DataPacketReceiveEvent $event) : void{
		$packet = $event->getPacket();
		$player = $event->getPlayer();
        	if($packet instanceof MobEquipmentPacket and $packet->windowId == ContainerIds::OFFHAND){
            		$offhand = $this->getOffhandInventory($player);
            		if(!$offhand->getItem(0)->equalsExact($packet->item)){
                		$offhand->sendContents($player);
                		$event->setCancelled();
                		return;
            		}
            		$offhand->setItem(0, $packet->item);
        	}
	}
	/**
	 * @ignoreCancelled true
	 * @priority MONITOR
	 */
	public function onDataPacketSend(DataPacketSendEvent $event) : void{
        	$packet = $event->getPacket();
        	$player = $event->getPlayer();
        	if($packet instanceof AddPlayerPacket){
            		$this->getOffhandInventory($player->getServer()->getPlayerExact($packet->username) ?? $player)->sendItem([$player]);
        	}
	}
	/**
	 * @priority MONITOR
	 */
	public function onDeath(PlayerDeathEvent $event) : void{
        	$player = $event->getPlayer();
        	$offhand = $this->getOffhandInventory($player);
        	if(!$event->getKeepInventory() and !empty($event->getDrops())){
            		$event->setDrops(array_merge($event->getDrops(), array_filter([$offhand->getItem(0)], function(Item $item): bool{
                		return !$item->hasEnchantment(Enchantment::VANISHING);
            		})));
            		$offhand->clearAll();
		}
        }
	private static $offhand = [];
	public function getOffHandInventory(Human $player): OffHandInventory{
        	$UUID = $player->getUniqueId()->toString();
        	$inventory = self::$offhand[$UUID] = self::$offhand[$UUID] ?? new OffhandInventory($player);
        	if($player instanceof Player){
            		$player->addWindow($inventory, ContainerIds::OFFHAND, true);
        	}
        	if($player->namedtag->hasTag("Offhand", ListTag::class)){
            		if(!$inventory->getItem(0)->equalsExact($item = Item::nbtDeserialize($player->namedtag->getListTag("Offhand")->get(0)))){
                		$inventory->setItem(0, $item);
            		}
        	}
        	return $inventory;
	}
}
