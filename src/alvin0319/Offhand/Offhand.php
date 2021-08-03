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

namespace alvin0319\Offhand;

use InvalidArgumentException;
use InvalidStateException;
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
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\SingletonTrait;

class Offhand extends PluginBase implements Listener{
	use SingletonTrait;

	public const TAG_OFFHAND = "offhand";

	/** @var PlayerOffhandInventory[] */
	protected array $inventories = [];

	public function onLoad() : void{
		self::setInstance($this);
	}

	public function onEnable() : void{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onPlayerLogin(PlayerLoginEvent $event) : void{
		$player = $event->getPlayer();
		$this->getOffhandInventory($player)->broadcastMobEquipment();
	}

	public function onPlayerQuit(PlayerQuitEvent $event) : void{
		$player = $event->getPlayer();
		$inv = $this->getOffhandInventory($player);
		$item = $inv->getItemInOffhand();
		$player->namedtag->setTag($item->nbtSerialize(-1, self::TAG_OFFHAND));
		unset($this->inventories[$player->getRawUniqueId()]);
	}

	public function getOffhandInventory(Player $player) : PlayerOffhandInventory{
		if(isset($this->inventories[$player->getRawUniqueId()])){
			return $this->inventories[$player->getRawUniqueId()];
		}
		$inv = new PlayerOffhandInventory($player);
		if($player->namedtag->hasTag(self::TAG_OFFHAND, CompoundTag::class)){
			$inv->setItemInOffhand(Item::nbtDeserialize($player->namedtag->getCompoundTag(self::TAG_OFFHAND)));
		}
		try{
			$player->addWindow($inv, ContainerIds::OFFHAND, true);
			$player->getDataPropertyManager()->setByte(Entity::DATA_COLOR, 0);
			return $this->inventories[$player->getRawUniqueId()] = $inv;
		}catch(InvalidStateException | InvalidArgumentException $e){
			throw new AssumptionFailedError("Failed to create Offhand instance, perhaps another plugin is using Offhand container id");
		}
	}

	public function onDataPacketReceive(DataPacketReceiveEvent $event) : void{
		$player = $event->getPlayer();
		$packet = $event->getPacket();
		if(($packet instanceof MobEquipmentPacket) && $packet->windowId === ContainerIds::OFFHAND){
			$event->setCancelled();
			if($packet->entityRuntimeId === $player->getId()){
				$inv = $this->getOffhandInventory($player);
				if($this->getConfig()->get("check-inventory-transaction", true) && !$inv->getItem($packet->hotbarSlot)->equalsExact($packet->item->getItemStack())){
					$this->getLogger()->debug("Tried to equip {$packet->item->getItemStack()} to {$player->getName()}, but have {$inv->getItem($packet->hotbarSlot)} in target slot");
					return;
				}
				$inv->setItemInOffhand($packet->item->getItemStack());
			}
		}
	}

	public function onDataPacketSend(DataPacketSendEvent $event) : void{
		$packet = $event->getPacket();
		$player = $event->getPlayer();
		if($packet instanceof AddPlayerPacket){
			$inv = $this->getOffhandInventory($player);
			$inv->broadcastMobEquipment();
		}
	}

	/**
	 * @param PlayerDeathEvent $event
	 *
	 * @priority HIGHEST
	 */
	public function onPlayerDeath(PlayerDeathEvent $event) : void{
		if(!$this->getConfig()->get("drop-item-on-death", false) && !$event->getKeepInventory()){
			return;
		}
		$event->setDrops($event->getDrops() + [$this->getOffhandInventory($event->getPlayer())->getItemInOffhand()]);
		$this->getOffhandInventory($event->getPlayer())->clearAll();
	}
}