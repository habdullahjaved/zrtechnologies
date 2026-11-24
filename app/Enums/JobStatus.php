<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

/**
 * Where a repair job is in the workshop (brief §12, "Repair jobs").
 */
enum JobStatus: string implements HasColor, HasIcon, HasLabel
{
    case Booked = 'booked';
    case CollectionScheduled = 'collection_scheduled';
    case Collected = 'collected';
    case Diagnosing = 'diagnosing';
    case AwaitingApproval = 'awaiting_approval';
    case AwaitingParts = 'awaiting_parts';
    case Repairing = 'repairing';
    case QualityCheck = 'quality_check';
    case ReadyForReturn = 'ready_for_return';
    case OutForDelivery = 'out_for_delivery';
    case Returned = 'returned';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Unrepairable = 'unrepairable';

    public function getLabel(): string
    {
        return match ($this) {
            self::Booked => 'Booked',
            self::CollectionScheduled => 'Collection scheduled',
            self::Collected => 'Collected',
            self::Diagnosing => 'Diagnosing',
            self::AwaitingApproval => 'Awaiting approval',
            self::AwaitingParts => 'Awaiting parts',
            self::Repairing => 'Repairing',
            self::QualityCheck => 'Quality check',
            self::ReadyForReturn => 'Ready for return',
            self::OutForDelivery => 'Out for delivery',
            self::Returned => 'Returned',
            self::Completed => 'Completed & paid',
            self::Cancelled => 'Cancelled',
            self::Unrepairable => 'Unrepairable',
        };
    }

    /**
     * What the customer sees on the tracking page.
     */
    public function customerLabel(): string
    {
        return match ($this) {
            self::Booked => 'Booking received',
            self::CollectionScheduled => 'Collection arranged',
            self::Collected => 'Collected',
            self::Diagnosing => 'Checking your iPhone',
            self::AwaitingApproval => 'Waiting for your go-ahead',
            self::AwaitingParts => 'Waiting for parts',
            self::Repairing => 'Being repaired',
            self::QualityCheck => 'Final checks',
            self::ReadyForReturn => 'Ready to come home',
            self::OutForDelivery => 'On its way back to you',
            self::Returned => 'Returned to you',
            self::Completed => 'Complete',
            self::Cancelled => 'Cancelled',
            self::Unrepairable => 'Could not be repaired',
        };
    }

    public function customerDescription(): string
    {
        return match ($this) {
            self::Booked => 'We have your booking and will confirm your collection.',
            self::CollectionScheduled => 'Your collection is booked in. Please have your iPhone backed up and ready.',
            self::Collected => 'We have your iPhone and it is on its way to our workshop.',
            self::Diagnosing => 'A technician is checking your iPhone to confirm the fault.',
            self::AwaitingApproval => 'The repair needs more than we quoted. We have been in touch and need your go-ahead before we continue.',
            self::AwaitingParts => 'We are waiting for a part to arrive. We will keep you posted.',
            self::Repairing => 'Your iPhone is being repaired.',
            self::QualityCheck => 'The repair is done and we are testing everything before it comes back.',
            self::ReadyForReturn => 'Your iPhone is repaired and ready to come back to you.',
            self::OutForDelivery => 'Your iPhone is on its way back to you today.',
            self::Returned => 'Your iPhone is back with you. Thank you for choosing us.',
            self::Completed => 'All done. Your warranty has started.',
            self::Cancelled => 'This booking has been cancelled.',
            self::Unrepairable => 'Sorry, we could not repair your iPhone. We will return it to you.',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Booked, self::CollectionScheduled => 'info',
            self::Collected, self::Diagnosing, self::Repairing, self::QualityCheck => 'primary',
            self::AwaitingApproval, self::AwaitingParts => 'warning',
            self::ReadyForReturn, self::OutForDelivery => 'info',
            self::Returned, self::Completed => 'success',
            self::Cancelled, self::Unrepairable => 'gray',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Booked => Heroicon::OutlinedCalendarDays,
            self::CollectionScheduled => Heroicon::OutlinedClock,
            self::Collected, self::OutForDelivery => Heroicon::OutlinedTruck,
            self::Diagnosing => Heroicon::OutlinedMagnifyingGlass,
            self::AwaitingApproval => Heroicon::OutlinedHandRaised,
            self::AwaitingParts => Heroicon::OutlinedCube,
            self::Repairing => Heroicon::OutlinedWrenchScrewdriver,
            self::QualityCheck => Heroicon::OutlinedClipboardDocumentCheck,
            self::ReadyForReturn => Heroicon::OutlinedCheckBadge,
            self::Returned, self::Completed => Heroicon::OutlinedCheckCircle,
            self::Cancelled, self::Unrepairable => Heroicon::OutlinedXCircle,
        };
    }

    /**
     * The statuses a job can move to next. Cancelling is only possible before
     * collection; after that the phone must go back to the customer.
     *
     * @return list<self>
     */
    public function nextStatuses(): array
    {
        return match ($this) {
            self::Booked => [self::CollectionScheduled, self::Collected, self::Cancelled],
            self::CollectionScheduled => [self::Collected, self::Booked, self::Cancelled],
            self::Collected => [self::Diagnosing, self::Repairing],
            self::Diagnosing => [self::Repairing, self::AwaitingApproval, self::AwaitingParts, self::Unrepairable],
            self::AwaitingApproval => [self::Repairing, self::AwaitingParts, self::ReadyForReturn],
            self::AwaitingParts => [self::Repairing, self::AwaitingApproval],
            self::Repairing => [self::QualityCheck, self::AwaitingParts, self::Unrepairable],
            self::QualityCheck => [self::ReadyForReturn, self::Repairing],
            self::ReadyForReturn => [self::OutForDelivery, self::Returned],
            self::OutForDelivery => [self::Returned, self::ReadyForReturn],
            self::Returned => [self::Completed],
            self::Unrepairable => [self::ReadyForReturn, self::OutForDelivery, self::Returned],
            self::Cancelled => [self::Booked],
            self::Completed => [],
        };
    }

    public function canMoveTo(self $status): bool
    {
        return in_array($status, $this->nextStatuses(), true);
    }

    /**
     * Customers are emailed about these changes unless staff untick "notify".
     */
    public function notifiesCustomerByDefault(): bool
    {
        return in_array($this, [
            self::CollectionScheduled, self::Collected, self::AwaitingApproval, self::AwaitingParts,
            self::ReadyForReturn, self::OutForDelivery, self::Returned, self::Completed,
            self::Cancelled, self::Unrepairable,
        ], true);
    }

    /**
     * The milestone column stamped the first time a job reaches this status.
     */
    public function milestoneColumn(): ?string
    {
        return match ($this) {
            self::Booked => 'booked_at',
            self::Collected => 'collected_at',
            self::ReadyForReturn => 'ready_at',
            self::Returned => 'returned_at',
            self::Completed => 'completed_at',
            self::Cancelled => 'cancelled_at',
            default => null,
        };
    }

    /**
     * Still in progress (not returned, completed or cancelled).
     */
    public function isActive(): bool
    {
        return ! in_array($this, [self::Returned, self::Completed, self::Cancelled], true);
    }

    /**
     * Holds a collection slot (counts against the window's capacity).
     */
    public function holdsCollectionSlot(): bool
    {
        return in_array($this, [self::Booked, self::CollectionScheduled], true);
    }

    /**
     * The customer-facing progress steps on the tracking page.
     *
     * @return list<self>
     */
    public static function trackingSteps(): array
    {
        return [self::Booked, self::Collected, self::Repairing, self::ReadyForReturn, self::Returned];
    }

    /**
     * Position on the tracking timeline (index into trackingSteps()).
     */
    public function trackingStep(): int
    {
        return match ($this) {
            self::Booked, self::CollectionScheduled, self::Cancelled => 0,
            self::Collected, self::Diagnosing, self::AwaitingApproval => 1,
            self::AwaitingParts, self::Repairing, self::QualityCheck, self::Unrepairable => 2,
            self::ReadyForReturn, self::OutForDelivery => 3,
            self::Returned, self::Completed => 4,
        };
    }

    /**
     * @return list<self>
     */
    public static function holdingSlot(): array
    {
        return array_values(array_filter(self::cases(), fn (self $status): bool => $status->holdsCollectionSlot()));
    }

    /**
     * @return list<self>
     */
    public static function active(): array
    {
        return array_values(array_filter(self::cases(), fn (self $status): bool => $status->isActive()));
    }
}
