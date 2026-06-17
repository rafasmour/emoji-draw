import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { joinRoom } from '@/requests/room/room';
import { dashboard } from '@/routes';
import { rooms as roomsIndex } from '@/routes/room';
import {
    type BreadcrumbItem,
    type PaginatedResponse,
    type RoomIndexRow,
    type SharedData,
} from '@/types';
import { Head, router, usePage } from '@inertiajs/react';
import {
    type ColumnDef,
    flexRender,
    getCoreRowModel,
    useReactTable,
} from '@tanstack/react-table';
import { ChevronLeft, ChevronRight, DoorOpen } from 'lucide-react';
import * as React from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
    {
        title: 'Rooms',
        href: roomsIndex().url,
    },
];

type RoomIndexPageProps = SharedData & {
    rooms: PaginatedResponse<RoomIndexRow>;
};

const columns: ColumnDef<RoomIndexRow>[] = [
    {
        accessorKey: 'name',
        header: 'Room',
        cell: ({ row }) => (
            <div className="flex flex-col gap-1">
                <span className="font-medium">{row.original.name}</span>
                <span className="text-xs text-muted-foreground">
                    Public lobby
                </span>
            </div>
        ),
    },
    {
        accessorKey: 'players',
        header: 'Players',
    },
    {
        id: 'actions',
        header: () => <div className="text-right">Action</div>,
        cell: ({ row }) => (
            <div className="flex justify-end">
                <Button onClick={() => joinRoom(row.original.id)}>Join</Button>
            </div>
        ),
    },
];

export default function RoomIndex() {
    const { rooms } = usePage<RoomIndexPageProps>().props;
    const pagination = React.useMemo(
        () => ({
            pageIndex: Math.max(rooms.current_page - 1, 0),
            pageSize: rooms.per_page,
        }),
        [rooms.current_page, rooms.per_page],
    );

    const goToPage = React.useCallback(
        (pageIndex: number) => {
            if (
                pageIndex < 0 ||
                pageIndex >= rooms.last_page ||
                pageIndex === pagination.pageIndex
            ) {
                return;
            }

            router.visit(
                roomsIndex.url({
                    query: { page: pageIndex + 1 },
                }),
                {
                    only: ['rooms'],
                    preserveScroll: true,
                    preserveState: true,
                },
            );
        },
        [pagination.pageIndex, rooms.last_page],
    );

    const table = useReactTable({
        data: rooms.data,
        columns,
        getCoreRowModel: getCoreRowModel(),
        manualPagination: true,
        pageCount: rooms.last_page,
        rowCount: rooms.total,
        state: {
            pagination,
        },
    });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Rooms" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
                <section className="rounded-3xl border border-sidebar-border/70 bg-background/95 p-6 shadow-xs dark:border-sidebar-border">
                    <div className="flex flex-col gap-2">
                        <p className="text-sm tracking-[0.2em] text-muted-foreground uppercase">
                            Public Lobbies
                        </p>
                        <div className="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
                            <div className="space-y-1">
                                <h1 className="text-3xl font-semibold tracking-tight">
                                    Rooms
                                </h1>
                                <p className="max-w-2xl text-sm text-muted-foreground">
                                    Browse active public rooms and jump directly
                                    into a lobby.
                                </p>
                            </div>
                            <div className="text-sm text-muted-foreground">
                                {rooms.total} public room
                                {rooms.total === 1 ? '' : 's'}
                            </div>
                        </div>
                    </div>
                </section>

                <section className="overflow-hidden rounded-3xl border border-sidebar-border/70 bg-background shadow-xs dark:border-sidebar-border">
                    {rooms.data.length > 0 ? (
                        <>
                            <Table>
                                <TableHeader>
                                    {table
                                        .getHeaderGroups()
                                        .map((headerGroup) => (
                                            <TableRow key={headerGroup.id}>
                                                {headerGroup.headers.map(
                                                    (header) => (
                                                        <TableHead
                                                            key={header.id}
                                                            className={
                                                                header.id ===
                                                                'actions'
                                                                    ? 'text-right'
                                                                    : undefined
                                                            }
                                                        >
                                                            {header.isPlaceholder
                                                                ? null
                                                                : flexRender(
                                                                      header
                                                                          .column
                                                                          .columnDef
                                                                          .header,
                                                                      header.getContext(),
                                                                  )}
                                                        </TableHead>
                                                    ),
                                                )}
                                            </TableRow>
                                        ))}
                                </TableHeader>
                                <TableBody>
                                    {table.getRowModel().rows.map((row) => (
                                        <TableRow key={row.id}>
                                            {row
                                                .getVisibleCells()
                                                .map((cell) => (
                                                    <TableCell
                                                        key={cell.id}
                                                        className={
                                                            cell.column.id ===
                                                            'actions'
                                                                ? 'text-right'
                                                                : undefined
                                                        }
                                                    >
                                                        {flexRender(
                                                            cell.column
                                                                .columnDef.cell,
                                                            cell.getContext(),
                                                        )}
                                                    </TableCell>
                                                ))}
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>

                            <div className="flex flex-col gap-4 border-t border-sidebar-border/70 px-6 py-4 text-sm md:flex-row md:items-center md:justify-between dark:border-sidebar-border">
                                <p className="text-muted-foreground">
                                    Showing {rooms.from} to {rooms.to} of{' '}
                                    {rooms.total} rooms
                                </p>
                                <div className="flex items-center gap-2 self-end md:self-auto">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() =>
                                            goToPage(pagination.pageIndex - 1)
                                        }
                                        disabled={!rooms.prev_page_url}
                                    >
                                        <ChevronLeft />
                                        Previous
                                    </Button>
                                    <span className="min-w-24 text-center text-muted-foreground">
                                        Page {rooms.current_page} of{' '}
                                        {rooms.last_page}
                                    </span>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() =>
                                            goToPage(pagination.pageIndex + 1)
                                        }
                                        disabled={!rooms.next_page_url}
                                    >
                                        Next
                                        <ChevronRight />
                                    </Button>
                                </div>
                            </div>
                        </>
                    ) : (
                        <div className="flex min-h-80 flex-col items-center justify-center gap-4 px-6 py-12 text-center">
                            <div className="rounded-full bg-muted p-4 text-muted-foreground">
                                <DoorOpen className="size-6" />
                            </div>
                            <div className="space-y-2">
                                <h2 className="text-xl font-semibold">
                                    No public rooms yet
                                </h2>
                                <p className="max-w-md text-sm text-muted-foreground">
                                    Create a room from the dashboard or wait for
                                    another player to open a public lobby.
                                </p>
                            </div>
                        </div>
                    )}
                </section>
            </div>
        </AppLayout>
    );
}
