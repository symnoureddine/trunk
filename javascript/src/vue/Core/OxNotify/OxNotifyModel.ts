export interface Notification {
    key: number
    type: NotificationType
    libelle: string
    delay: number
    callback?: Function
    callbackDone: boolean
}

export enum NotificationType {
    success = "success",
    info = "info",
    warning = "warning",
    error = "error"
}

export enum NotificationDelay {
    none = 0,
    short = 2000,
    medium = 5000,
    long = 10000
}

export interface NotificationOpt {
    delay?: NotificationDelay
    callback?: Function
}
