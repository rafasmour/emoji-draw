import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';
import { FormEvent } from 'react';

interface RoomChatMessageFormProps {
    value: string;
    onChange: (value: string) => void;
    onSubmit: (event: FormEvent<HTMLFormElement>) => void;
    placeholder: string;
    className?: string;
    inputClassName?: string;
    submitLabel?: string;
}

export function RoomChatMessageForm({
    value,
    onChange,
    onSubmit,
    placeholder,
    className,
    inputClassName,
    submitLabel = 'Send',
}: RoomChatMessageFormProps) {
    return (
        <form
            className={cn(
                'flex w-full items-center justify-center gap-2',
                className,
            )}
            onSubmit={onSubmit}
        >
            <Input
                value={value}
                onChange={(event) => onChange(event.target.value)}
                type="text"
                placeholder={placeholder}
                className={inputClassName}
            />
            <Button type="submit">{submitLabel}</Button>
        </form>
    );
}
