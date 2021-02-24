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

use pocketmine\entity\Entity;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\MobEquipmentPacket;
use pocketmine\network\mcpe\protocol\types\ContainerIds;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;

class OffHand extends PluginBase implements Listener{
	use SingletonTrait;

	/** @var PlayerOffHandInventory[] */
	protected array $inventories = [];

	public function onLoad() : void{
		self::setInstance($this);
	}

	public function onEnable() : void{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}
	
	public function getOffHandInventory(Player $player) : PlayerOffhandInventory{
		if(isset($this->inventories[$player->getRawUniqueId()])){
			return $this->inventories[$player->getRawUniqueId()];
		}
		$inv = new OffHandInventory($player);
		if($player->namedtag->hasTag("offhand", CompoundTag::class)){
			$inv->setItemInOffhand(Item::nbtDeserialize($player->namedtag->getCompoundTag("offhand")));
		}
		$player->addWindow($inv, ContainerIds::OFFHAND, true);
		$player->getDataPropertyManager()->setByte(Entity::DATA_COLOR, 0);
		return $this->inventories[$player->getRawUniqueId()] = $inv;
	}


	public function onPlayerLogin(PlayerLoginEvent $event){
		$player = $event->getPlayer();
		$this->getOffHandInventory($player)->broadcastMobEquipment();
	}

	public function onPlayerQuit(PlayerQuitEvent $event) : void{
		$player = $event->getPlayer();
		$inv = $this->getOffHandInventory($player);
		$item = $inv->getItemInOffHand();
		$player->namedtag->setTag($item->nbtSerialize(-1, "offhand"));
	}

	public function onDataPacketReceive(DataPacketReceiveEvent $event) : void{
		$player = $event->getPlayer();
		$packet = $event->getPacket();
		if($packet instanceof MobEquipmentPacket){
			if($packet->windowId === ContainerIds::OFFHAND){
				$event->setCancelled();
				if($packet->entityRuntimeId === $player->getId()){
					$inv = $this->getOffHandInventory($player);
					if($this->getConfig()->get("check-inventory-transaction", true)){
						if(!$inv->getItem($packet->hotbarSlot)->equalsExact($packet->item)){
							$this->getLogger()->debug("Tried to equip {$packet->item} to {$player->getName()}, but have {$inv->getItem($packet->hotbarSlot)} in target slot");
							return;
						}
					}
					$inv->setItemInOffHand($packet->item);
				}
			}
		}
	}

	public function onDataPacketSend(DataPacketSendEvent $event) : void{
		$packet = $event->getPacket();
		$player = $event->getPlayer();
		if($packet instanceof AddPlayerPacket){
			$inv = $this->getOffHandInventory($player);
			$inv->broadcastMobEquipment();
		}
	}

	/**
	 * @param PlayerDeathEvent $event
	 * @priority MONITOR
	 */
	public function onPlayerDeath(PlayerDeathEvent $event) : void{
		if(!$this->getConfig()->get("drop-item-on-death", false) && !$event->getKeepInventory()){
			return;
		}
		$event->setDrops($event->getDrops() + [$this->getOffHandInventory($event->getPlayer())->getItemInOffHand()]);
		$this->getOffHandInventory($event->getPlayer())->clearAll();
	}
}