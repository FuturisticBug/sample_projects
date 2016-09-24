//
//  AppDelegate.m
//  Connex-New
//
//  Created by Saptarshi's  on 6/24/16.
//  Copyright © 2016 esolz. All rights reserved.
//

#import "AppDelegate.h"
#import <AudioToolbox/AudioServices.h>
#import "ARTCVideoChatViewController.h"
#import <AVFoundation/AVFoundation.h>
#import "CNGlobalViewController.h"
#import <Fabric/Fabric.h>
#import <DigitsKit/DigitsKit.h>
#import <Crashlytics/Crashlytics.h>
#import "CNLogViewController.h"
#import "CNGlobalHeader.h"
#import "iVersion.h"

static int kNumberOfContact = 300;


@interface AppDelegate ()
{
    NSMutableDictionary *userInfoDic;
    SystemSoundID soundId;
    int conversationTime,numberOfIteration;
//    BOOL isFromIdentifier;
    int timeCount;
    CNContactStore *contactStore;
    NSMutableArray *groupsOfContact;
    NSMutableArray *contactArray,*finalContactArray;
    NSDictionary *remoteNotif;
    NSTimer *thirdCallTimer;
    UILocalNotification *callNotification,*missedCallNotification;
    NSString *oldHoldState,*profilePicLink;
}

@end

@implementation AppDelegate
@synthesize callerName;

// iVersion works
+ (void)initialize
{
    [iVersion sharedInstance].checkPeriod=0;
    [iVersion sharedInstance].checkAtLaunch=YES;
    [iVersion sharedInstance].applicationBundleID = @"com.esolz.ConnexNew";
}

- (BOOL)application:(UIApplication *)application didFinishLaunchingWithOptions:(NSDictionary *)launchOptions
{
    oldHoldState=@"";
    remoteNotif = [launchOptions objectForKey:UIApplicationLaunchOptionsLocalNotificationKey];
    
    [[UIApplication sharedApplication] cancelAllLocalNotifications];
    
    [self voipRegistration];
    
    _audioSession =[AVAudioSession sharedInstance];
    
    navigation=(UINavigationController *)self.window.rootViewController;
    
    _dateFormatter = [[NSDateFormatter alloc]init];
    [_dateFormatter setTimeZone:[NSTimeZone systemTimeZone]];
    [_dateFormatter setLocale:[NSLocale systemLocale]];
    
    [[UIApplication sharedApplication] setApplicationIconBadgeNumber:-1];
    [[UIApplication sharedApplication] setApplicationIconBadgeNumber:0];
    [[NSUserDefaults standardUserDefaults]setObject:[NSString stringWithFormat:@"0"] forKey:CNBADGECOUNT];
    
    _deviceSize=[[UIScreen mainScreen]bounds].size;
    
#if __IPHONE_OS_VERSION_MAX_ALLOWED >= 80000
    if ([application respondsToSelector:@selector(registerUserNotificationSettings:)])
    {
        // category set up for push notification buttons.
        // use registerUserNotificationSettings
        UIMutableUserNotificationAction *acceptAction =[[UIMutableUserNotificationAction alloc] init];
        
        // The identifier that you use internally to handle the action.
        acceptAction.identifier = @"ACCEPT_IDENTIFIER";
        
        // The localized title of the action button.
        acceptAction.title = @"Accept";
        
        // Specifies whether the app must be in the foreground to perform the action.
        acceptAction.activationMode = UIUserNotificationActivationModeForeground;
        
        // Destructive actions are highlighted appropriately to indicate their nature.
        acceptAction.destructive = NO;
        
        // Indicates whether user authentication is required to perform the action.
        acceptAction.authenticationRequired = NO;
        
        UIMutableUserNotificationAction *denyAction =
        [[UIMutableUserNotificationAction alloc] init];
        
        // The identifier that you use internally to handle the action.
        denyAction.identifier = @"DENY_IDENTIFIER";
        
        // The localized title of the action button.
        denyAction.title = @"Reject";
        
        // Specifies whether the app must be in the foreground to perform the action.
        denyAction.activationMode = UIUserNotificationActivationModeBackground;
        
        // Destructive actions are highlighted appropriately to indicate their nature.
        denyAction.destructive = YES;
        
        // Indicates whether user authentication is required to perform the action.
        denyAction.authenticationRequired = NO;
        
        UIMutableUserNotificationCategory *inviteCategory =
        [[UIMutableUserNotificationCategory alloc] init];
        
        // Identifier to include in your push payload and local notification
        inviteCategory.identifier = @"INVITE_CATEGORY";
        
        // Set the actions to display in the default context
        [inviteCategory setActions:@[acceptAction,denyAction]
                        forContext:UIUserNotificationActionContextDefault];
        
        // Set the actions to display in a minimal context
        [inviteCategory setActions:@[acceptAction,denyAction]
                        forContext:UIUserNotificationActionContextMinimal];
        
        UIUserNotificationType types = UIUserNotificationTypeBadge |
        UIUserNotificationTypeSound | UIUserNotificationTypeAlert;
        
        NSSet *categories = [NSSet setWithObjects:inviteCategory,nil];
        
        UIUserNotificationSettings *settings =[UIUserNotificationSettings settingsForTypes:types categories:categories];
        
        [UIApplication sharedApplication];
        
        [[UIApplication sharedApplication] registerUserNotificationSettings:settings];
        
        
        [application registerForRemoteNotifications];
    }
    else
    {
        [[UIApplication sharedApplication] registerForRemoteNotifications];
    }
#else
    {
        [[UIApplication sharedApplication] registerForRemoteNotificationTypes: (UIRemoteNotificationTypeAlert | UIRemoteNotificationTypeBadge |UIRemoteNotificationTypeSound)];
    }
#endif
    [Fabric with:@[[Digits class],[Crashlytics class]]];
    [[NSUserDefaults standardUserDefaults]setObject:connexCallStatusIdle forKey:connexCallStatus];
    
    return YES;
}

#pragma mark -- VoIP PushKit --

//Register for VoIP notifications
- (void) voipRegistration {
    dispatch_queue_t mainQueue = dispatch_get_main_queue();
    // Create a push registry object
    PKPushRegistry * voipRegistry = [[PKPushRegistry alloc] initWithQueue: mainQueue];
    // Set the registry's delegate to self
    voipRegistry.delegate = self;
    // Set the push type to VoIP
    voipRegistry.desiredPushTypes = [NSSet setWithObject:PKPushTypeVoIP];
}

// Handle updated push credentials and saving device token
- (void)pushRegistry:(PKPushRegistry *)registry didUpdatePushCredentials: (PKPushCredentials *)credentials forType:(NSString *)type
{
    // Register VoIP push token (a property of PKPushCredentials) with server
    NSString *devToken = [[[[credentials.token description] stringByReplacingOccurrencesOfString:@"<"withString:@""] stringByReplacingOccurrencesOfString:@">" withString:@""] stringByReplacingOccurrencesOfString:@" " withString: @""];
    NSLog(@"pushkit credentials %@",devToken);
    [[NSUserDefaults standardUserDefaults] setObject:devToken forKey:@"deviceToken"];
    [[NSUserDefaults standardUserDefaults] synchronize];
    [self testAlert:[NSString stringWithFormat:@"%@",devToken]];
}

// this method get called each time while receives push

- (void)pushRegistry:(PKPushRegistry *)registry didReceiveIncomingPushWithPayload:(PKPushPayload *)payload forType:(NSString *)type
{
    // Process the received push
    remoteNotif = [payload.dictionaryPayload mutableCopy];
    
    //fetching contact name from local db based on phone number received through push.
    NSString *phoneNumber = [[payload.dictionaryPayload objectForKey:@"count_val"] objectForKey:@"phone"];
    callerName = [self fetchName:phoneNumber];
    NSCalendar *c = [NSCalendar currentCalendar];
    NSDate *d1 = [NSDate date];
    NSDateFormatter *dateFormat = [[NSDateFormatter alloc]init];
    [dateFormat setTimeZone:[NSTimeZone timeZoneWithAbbreviation:[[payload.dictionaryPayload objectForKey:@"count_val"] objectForKey:@"server_timezone"]]];
    [dateFormat setDateFormat:@"yyyy-MM-dd HH:mm:ss"];
    NSDate *d2 = [dateFormat dateFromString:[[payload.dictionaryPayload objectForKey:@"count_val"] objectForKey:@"server_datetime"]];//[NSDate dateWithTimeIntervalSince1970:[roomID intValue]];//2012-06-22
    NSDateComponents *components = [c components:NSCalendarUnitHour|NSCalendarUnitMinute|NSCalendarUnitSecond fromDate:d2 toDate:d1 options:0];
    NSLog(@"date d1 =%@ d2=%@",d1,d2);
    
    NSLog(@"hour %ld minute %ld second %ld",(long)components.hour,(long)components.minute,(long)components.second);
    CNGlobalViewController *CTGVC=[[CNGlobalViewController alloc]init];
    [self testAlert:[NSString stringWithFormat:@"%@ %@",d1,d2]];
    if (components.hour == 0 && (components.minute == 0||components.minute==1) && components.second < 60)
    {
        UIApplicationState state = [[UIApplication sharedApplication]applicationState];
        if (state == UIApplicationStateInactive || state == UIApplicationStateBackground)
        {
            NSString *callMode=[[payload.dictionaryPayload objectForKey:@"count_val"]objectForKey:@"mod"];
            [[UIApplication sharedApplication]cancelLocalNotification:callNotification];
            [[UIApplication sharedApplication]cancelLocalNotification:missedCallNotification];
            [[UIApplication sharedApplication]cancelAllLocalNotifications];
            if ([callMode isEqualToString:@"voice"] || [callMode isEqualToString:@"video"])
            {
                dispatch_async(dispatch_get_main_queue(), ^{
                    [_callTimer invalidate];
                    _callTimer = nil;
                    _callTimer=[NSTimer scheduledTimerWithTimeInterval:1.0f target:self selector:@selector(checkRingingTime:) userInfo:nil repeats:YES];
                });
                
                callNotification = [[UILocalNotification alloc] init];
                
                NSString *msg =[[payload.dictionaryPayload objectForKey:@"count_val"]objectForKey:@"message"];
                callNotification.fireDate = [NSDate dateWithTimeIntervalSinceNow:0];
                callNotification.alertAction = nil;
                callNotification.soundName = @"iphone.caf";
                callNotification.alertBody = [NSString stringWithFormat:@"%@",msg];
                callNotification.category=@"INVITE_CATEGORY";
                callNotification.repeatInterval=0;
                callNotification.userInfo=payload.dictionaryPayload;
                [[UIApplication sharedApplication] scheduleLocalNotification:callNotification];
                
                timeCount=0;
                
            }
            else
            {
                dispatch_async(dispatch_get_main_queue(), ^{
                    [_callTimer invalidate];
                    _callTimer = nil;
                });
                
                CNGlobalViewController *CTGVC=[[CNGlobalViewController alloc]init];
                
                [[UIApplication sharedApplication]cancelLocalNotification:callNotification];
                [[UIApplication sharedApplication]cancelLocalNotification:missedCallNotification];
                [[UIApplication sharedApplication]cancelAllLocalNotifications];
                
                NSString *badgeCountVal = [[NSUserDefaults standardUserDefaults]objectForKey:CNBADGECOUNT];
                int badgeCount = [badgeCountVal intValue];
                if (badgeCount <= 0)
                    badgeCount = 1;
                else
                    badgeCount += 1;
                [[NSUserDefaults standardUserDefaults]setObject:[NSString stringWithFormat:@"%d",badgeCount] forKey:CNBADGECOUNT];
                [[NSUserDefaults standardUserDefaults]synchronize];
                missedCallNotification = [[UILocalNotification alloc] init];
                
                NSString *msg =[[payload.dictionaryPayload objectForKey:@"count_val"]objectForKey:@"message"];
                missedCallNotification.fireDate = [NSDate dateWithTimeIntervalSinceNow:0];
                missedCallNotification.alertAction = nil;
                missedCallNotification.soundName = UILocalNotificationDefaultSoundName;
                missedCallNotification.alertBody = [NSString stringWithFormat:@"%@",msg];
                missedCallNotification.alertAction = NSLocalizedString(@"Read Msg", @"view");
                missedCallNotification.repeatInterval=0;
                missedCallNotification.applicationIconBadgeNumber = badgeCount;
                missedCallNotification.userInfo=payload.dictionaryPayload;
                [[UIApplication sharedApplication] scheduleLocalNotification:missedCallNotification];
                
                [_dateFormatter setDateFormat:@"hh:mm a"];
                NSDate *time = [_dateFormatter dateFromString:[_dateFormatter stringFromDate:[NSDate date]]];
                [_dateFormatter setDateFormat:@"dd-MM-yyyy"];
                NSString *date = [_dateFormatter stringFromDate:[NSDate date]];
                [CTGVC addToCallLogWithName:callerName phone:phoneNumber typeOfCall:[NSNumber numberWithInt:0] time:time date:date fullDate:[NSDate date]];
            }
        }
        else
        {
            NSString *status = [[NSUserDefaults standardUserDefaults]objectForKey:connexCallStatus];
            if (![status isEqualToString:connexCallStatusOnCall])
            {
                userInfoDic=[payload.dictionaryPayload mutableCopy];
                NSString *roomID=[[userInfoDic objectForKey:@"count_val"] objectForKey:@"roomId"];
                NSString *senderID=[[userInfoDic objectForKey:@"count_val"] objectForKey:@"sender_id"];
                
                
                [[[UIApplication sharedApplication] keyWindow] endEditing:YES];
                
                NSString *pushType=[[userInfoDic objectForKey:@"count_val"]objectForKey:@"mod"];//@"status"
                if ([pushType isEqualToString:@"voice"] || [pushType isEqualToString:@"video"])
                {
                    NSString *status = [[NSUserDefaults standardUserDefaults]objectForKey:connexCallStatus];
                    if ([status isEqualToString:connexCallStatusIdle])
                    {
                        if (![navigation.visibleViewController isKindOfClass:[ARTCVideoChatViewController class]])
                        {
                            [[NSUserDefaults standardUserDefaults]setObject:connexCallStatusRinging forKey:connexCallStatus];
                            [_incomingCallView removeFromSuperview];
                            _incomingCallView=nil;
                            _incomingCallView=[[[NSBundle mainBundle]loadNibNamed:@"Dialler" owner:self options:nil]objectAtIndex:0];
                            [_incomingCallView setFrame:CGRectMake(0, 0, FULLWIDTH, FULLHEIGHT)];
                            [_incomingCallView setAlpha:0.0];
                            [navigation.visibleViewController.view addSubview:_incomingCallView];
                            [UIView animateWithDuration:0.2 animations:^{
                                [_incomingCallView setAlpha:1.0];
                            }];
                            
                            UIImageView *backImage=(UIImageView *)[_incomingCallView viewWithTag:1];
                            [backImage setBackgroundColor:[UIColor clearColor]];
                            if (profilePicLink != nil)
                                [backImage setImage:[UIImage imageWithContentsOfFile:profilePicLink]];
                            
                            UILabel *nameLBL=(UILabel *)[_incomingCallView viewWithTag:2];
                            [nameLBL setText:[callerName capitalizedString]];
                            
                            UILabel *callTypeLBL = (UILabel *)[_incomingCallView viewWithTag:3];
                            [callTypeLBL setText:[NSString stringWithFormat:@"Connex %@ Call",[pushType capitalizedString]]];
                            
                            UIButton *declineBTN=(UIButton *)[_incomingCallView viewWithTag:8];
                            [CTGVC setRoundCornertoView:declineBTN withBorderColor:nil borderWidth:0.0f WithRadius:.5 dependsOnHeight:YES];
                            UIButton *acceptBTN=(UIButton *)[_incomingCallView viewWithTag:9];
                            [CTGVC setRoundCornertoView:acceptBTN withBorderColor:nil borderWidth:0.0f WithRadius:.5 dependsOnHeight:YES];
                            
                            [declineBTN addTarget:self action:@selector(callResponse:) forControlEvents:UIControlEventTouchUpInside];
                            [acceptBTN addTarget:self action:@selector(callResponse:) forControlEvents:UIControlEventTouchUpInside];
                            
                            
                            NSString *path = [[NSBundle mainBundle] pathForResource:@"iphone" ofType:@"caf"];
                            NSURL *url=[NSURL URLWithString:path];
                            AudioServicesCreateSystemSoundID((__bridge CFURLRef)[NSURL fileURLWithPath:path], &soundId);
                            AudioServicesCreateSystemSoundID((__bridge CFURLRef _Nonnull)(url), &soundId);
                            AudioServicesPlaySystemSound(soundId);
                            timeCount=0;
                            
                            _callerNumber = phoneNumber;
                            dispatch_async(dispatch_get_main_queue(), ^{
                                [_callTimer invalidate];
                                _callTimer = nil;
                                _callTimer=[NSTimer scheduledTimerWithTimeInterval:1.0 target:self selector:@selector(checkRingingTime:) userInfo:nil repeats:YES];
                            });
                        }
                    }
                }
                else if ([pushType isEqualToString:@"reject"] || [pushType isEqualToString:@"callEnded"])
                {
                    
                    if ([phoneNumber isEqualToString:_callerNumber])
                    {
                        NSString *status = [[NSUserDefaults standardUserDefaults]objectForKey:connexCallStatus];
                        if ([status isEqualToString:connexCallStatusRinging] || [status isEqualToString:connexCallStatusOnCall] || [status isEqualToString:connexCallStatusCalling])
                        {
                            
                            [[NSUserDefaults standardUserDefaults]setObject:connexCallStatusIdle forKey:connexCallStatus];
                            if (_incomingCallView !=nil)
                            {
                                [_dateFormatter setDateFormat:@"hh:mm a"];
                                NSDate *time = [_dateFormatter dateFromString:[_dateFormatter stringFromDate:[NSDate date]]];
                                [_dateFormatter setDateFormat:@"dd-MM-yyyy"];
                                NSString *date = [_dateFormatter stringFromDate:[NSDate date]];
                                [CTGVC addToCallLogWithName:callerName phone:phoneNumber typeOfCall:[NSNumber numberWithInt:0] time:time date:date fullDate:[NSDate date]];
                                
                                AudioServicesDisposeSystemSoundID(soundId);
                                [UIView animateWithDuration:0.1 animations:^{
                                    [_incomingCallView setAlpha:0.0];
                                } completion:^(BOOL finished) {
                                    if (finished)
                                    {
                                        [_incomingCallView removeFromSuperview];
                                        _incomingCallView = nil;
                                    }
                                }];
                            }
                            else if (_conversationView != nil)
                            {
                                [_dateFormatter setDateFormat:@"hh:mm a"];
                                NSDate *time = [_dateFormatter dateFromString:[_dateFormatter stringFromDate:[NSDate date]]];
                                [_dateFormatter setDateFormat:@"dd-MM-yyyy"];
                                NSString *date = [_dateFormatter stringFromDate:[NSDate date]];
                                [CTGVC addToCallLogWithName:callerName phone:phoneNumber typeOfCall:[NSNumber numberWithInt:2] time:time date:date fullDate:[NSDate date]];
                                
                                if (_conversationView !=nil)
                                {
                                    [UIView animateWithDuration:0.1 animations:^{
                                        [_conversationView setAlpha:0.0];
                                    } completion:^(BOOL finished) {
                                        if (finished)
                                        {
                                            [_conversationView removeFromSuperview];
                                            _conversationView = nil;
                                        }
                                    }];
                                }
                            }
                            else if (_outgoingCallView !=nil)
                            {
                                [_dateFormatter setDateFormat:@"hh:mm a"];
                                NSDate *time = [_dateFormatter dateFromString:[_dateFormatter stringFromDate:[NSDate date]]];
                                [_dateFormatter setDateFormat:@"dd-MM-yyyy"];
                                NSString *date = [_dateFormatter stringFromDate:[NSDate date]];
                                [CTGVC addToCallLogWithName:callerName phone:phoneNumber typeOfCall:[NSNumber numberWithInt:1] time:time date:date fullDate:[NSDate date]];
                                
                                [_audioPlayer stop];
                                _audioPlayer = nil;
                                NSString *soundFilePath = [[NSBundle mainBundle] pathForResource:@"phone-busy" ofType: @"mp3"];
                                NSURL *fileURL = [[NSURL alloc] initFileURLWithPath:soundFilePath ];
                                _audioPlayer = [[AVAudioPlayer alloc] initWithContentsOfURL:fileURL error:nil];
                                _audioPlayer.numberOfLoops = 1; //infinite loop
                                [_audioPlayer play];
                                
                                double delayInSeconds = 2.0;
                                dispatch_time_t popTime = dispatch_time(DISPATCH_TIME_NOW, (int64_t)(delayInSeconds * NSEC_PER_SEC));
                                dispatch_after(popTime, dispatch_get_main_queue(), ^(void){
                                    [UIView animateWithDuration:0.1 animations:^{
                                        [_outgoingCallView setAlpha:0.0];
                                    } completion:^(BOOL finished) {
                                        if (finished)
                                        {
                                            [_audioPlayer stop];
                                            [_outgoingCallView removeFromSuperview];
                                            _outgoingCallView=nil;
                                        }
                                    }];
                                });
                            }
                        }
                    }
                    else if ([navigation.visibleViewController isKindOfClass:[ARTCVideoChatViewController class]])
                    {
                        if ([pushType isEqualToString:@"callEnded"])
                        {
                            [[NSNotificationCenter defaultCenter]postNotificationName:@"CallEndedByRemoteUser" object:nil userInfo:payload.dictionaryPayload];
                        }
                    }
                }
                else if ([pushType isEqualToString:@"voiceaccept"] || [pushType isEqualToString:@"videoaccept"])
                {
                    if (![_endedMeetingID isEqualToString:roomID])
                    {
                        [_audioPlayer stop];
                        _audioPlayer = nil;
                        NSString *callMode=[[userInfoDic objectForKey:@"count_val"]objectForKey:@"mod"];
                        if ([callMode isEqualToString:@"videoaccept"])
                        {
                            if ([[CTGVC checkStatusForMicrophoneAndCameraPermission]isEqualToString:@"OK"])
                            {
                                [[NSUserDefaults standardUserDefaults]setObject:connexCallStatusOnCall forKey:connexCallStatus];
                                [_outgoingCallView removeFromSuperview];
                                _outgoingCallView = nil;
                                ARTCVideoChatViewController *ARTCVCVC;
                                ARTCVCVC =[[UIStoryboard storyboardWithName:@"Video" bundle:nil]instantiateViewControllerWithIdentifier:@"ARTCVideoChatViewController"];
                                ARTCVCVC.roomID = roomID;
                                ARTCVCVC.typeOfCall = pushType;
                                ARTCVCVC.userName=callerName;
                                ARTCVCVC.receiverID=senderID;
                                ARTCVCVC.profileImageUrl=profilePicLink;
                                CATransition *Transition=[CATransition animation];
                                [Transition setDuration:0.1f];
                                [Transition setTimingFunction:[CAMediaTimingFunction functionWithName:kCAMediaTimingFunctionEaseIn]];
                                [Transition setType:kCAMediaTimingFunctionEaseIn];
                                [[[navigation view] layer] addAnimation:Transition forKey:nil];
                                [navigation pushViewController:ARTCVCVC animated:NO];
                            }
                            else
                            {
                                [[NSUserDefaults standardUserDefaults]setObject:connexCallStatusIdle forKey:connexCallStatus];
                                [CTGVC showAlertForCameraAndMicrophonePermissions];
                            }
                        }
                        else if ([callMode isEqualToString:@"voiceaccept"])
                        {
                            if ([[CTGVC checkStatusForMicrophoneAndCameraPermission]isEqualToString:@"OK"])
                            {
                                [[NSUserDefaults standardUserDefaults]setObject:connexCallStatusOnCall forKey:connexCallStatus];
                                [_outgoingCallView removeFromSuperview];
                                _outgoingCallView=nil;
                                
                                ARTCVideoChatViewController *ARTCVCVC;
                                ARTCVCVC =[[UIStoryboard storyboardWithName:@"Video" bundle:nil]instantiateViewControllerWithIdentifier:@"ARTCVideoChatViewController"];
                                ARTCVCVC.roomID = roomID;
                                ARTCVCVC.typeOfCall = pushType;
                                ARTCVCVC.userName=callerName;
                                ARTCVCVC.receiverID=senderID;
                                ARTCVCVC.profileImageUrl=profilePicLink;
                                CATransition *Transition=[CATransition animation];
                                [Transition setDuration:0.1f];
                                [Transition setTimingFunction:[CAMediaTimingFunction functionWithName:kCAMediaTimingFunctionEaseIn]];
                                [Transition setType:kCAMediaTimingFunctionEaseIn];
                                [[[navigation view] layer] addAnimation:Transition forKey:nil];
                                [navigation pushViewController:ARTCVCVC animated:NO];
                            }
                            else
                            {
                                [[NSUserDefaults standardUserDefaults]setObject:connexCallStatusIdle forKey:connexCallStatus];
                                [CTGVC showAlertForCameraAndMicrophonePermissions];
                            }
                        }
                    }
                }
            }
            else if ([[[payload.dictionaryPayload objectForKey:@"count_val"] objectForKey:@"mod"] isEqualToString:@"onhold"] || [[[payload.dictionaryPayload objectForKey:@"count_val"] objectForKey:@"mod"] isEqualToString:@"unhold"])
            {
                if (![oldHoldState isEqualToString:[[payload.dictionaryPayload objectForKey:@"count_val"] objectForKey:@"mod"]] && [[[payload.dictionaryPayload objectForKey:@"count_val"] objectForKey:@"mod"] isEqualToString:@"onhold"])
                {
                    [[NSNotificationCenter defaultCenter]postNotificationName:@"UserKeptOnHold" object:nil userInfo:@{@"type":[[payload.dictionaryPayload objectForKey:@"count_val"] objectForKey:@"mod"]}];
                    oldHoldState = [[payload.dictionaryPayload objectForKey:@"count_val"] objectForKey:@"mod"];
                }
                else if([[[payload.dictionaryPayload objectForKey:@"count_val"] objectForKey:@"mod"] isEqualToString:@"unhold"])
                {
                    oldHoldState = [[payload.dictionaryPayload objectForKey:@"count_val"] objectForKey:@"mod"];
                }
                
            }
            else
            {
                
                NSString *typeOfCall=[[payload.dictionaryPayload objectForKey:@"count_val"] objectForKey:@"mod"];
//                [self testAlert:[NSString stringWithFormat:@"%@ %@ %@",_callerNumber,phoneNumber,typeOfCall]];
//                if ([_callerNumber isEqualToString:phoneNumber] && ([typeOfCall isEqualToString:@"reject"] || [typeOfCall isEqualToString:@"callEnded"]) && [navigation.visibleViewController isKindOfClass:[ARTCVideoChatViewController class]])
//                {
//                    [[NSNotificationCenter defaultCenter]postNotificationName:@"CallEndedByRemoteUser" object:nil userInfo:payload.dictionaryPayload];
//                }
                 if (![_callerNumber isEqualToString:phoneNumber] && ([typeOfCall isEqualToString:@"voice"] || [typeOfCall isEqualToString:@"video"]))
                {
                    _thirdCallDic=[payload.dictionaryPayload mutableCopy];
                    
                    //[self testAlert:[NSString stringWithFormat:@"bool yes %@",typeOfCall]];
                    if ([typeOfCall isEqualToString:@"voice"] || [typeOfCall isEqualToString:@"video"])
                    {
                        [_thirdCallView removeFromSuperview];
                        _thirdCallView = nil;
                        dispatch_async(dispatch_get_main_queue(), ^{
                            [self showThirdCallView];
                            timeCount=0;
                            thirdCallTimer=[NSTimer scheduledTimerWithTimeInterval:1.0f target:self selector:@selector(checkThirdCallRingingTime:) userInfo:nil repeats:YES];
                        });
                    }
                    else if ([typeOfCall isEqualToString:@"reject"] || [typeOfCall isEqualToString:@"callEnded"])
                    {
                        [UIView animateWithDuration:0.1 animations:^{
                            [_thirdCallView setAlpha:0.0];
                        } completion:^(BOOL finished) {
                            if (finished)
                            {
                                [_thirdCallView removeFromSuperview];
                                _thirdCallView = nil;
                                _thirdCallDic=nil;
                            }
                        }];
                    }
                }
                else if ([_callerNumber isEqualToString:phoneNumber] && ([typeOfCall isEqualToString:@"voice"] || [typeOfCall isEqualToString:@"video"]))
                {
                    userInfoDic=[payload.dictionaryPayload mutableCopy];

                    [[NSNotificationCenter defaultCenter]postNotificationName:@"CallEndedByRemoteUser" object:nil userInfo:payload.dictionaryPayload];
                    
                    double delayInSeconds = 1.0;
                    dispatch_time_t popTime = dispatch_time(DISPATCH_TIME_NOW, (int64_t)(delayInSeconds * NSEC_PER_SEC));
                    dispatch_after(popTime, dispatch_get_main_queue(), ^(void){
                        [[NSUserDefaults standardUserDefaults]setObject:connexCallStatusRinging forKey:connexCallStatus];
                        [_incomingCallView removeFromSuperview];
                        _incomingCallView=nil;
                        _incomingCallView=[[[NSBundle mainBundle]loadNibNamed:@"Dialler" owner:self options:nil]objectAtIndex:0];
                        [_incomingCallView setFrame:CGRectMake(0, 0, FULLWIDTH, FULLHEIGHT)];
                        [_incomingCallView setAlpha:0.0];
                        [navigation.visibleViewController.view addSubview:_incomingCallView];
                        [UIView animateWithDuration:0.2 animations:^{
                            [_incomingCallView setAlpha:1.0];
                        }];
                        
                        UIImageView *backImage=(UIImageView *)[_incomingCallView viewWithTag:1];
                        [backImage setBackgroundColor:[UIColor clearColor]];
                        if (profilePicLink != nil)
                            [backImage setImage:[UIImage imageWithContentsOfFile:profilePicLink]];
                        
                        UILabel *nameLBL=(UILabel *)[_incomingCallView viewWithTag:2];
                        [nameLBL setText:[callerName capitalizedString]];
                        
                        UILabel *callTypeLBL = (UILabel *)[_incomingCallView viewWithTag:3];
                        [callTypeLBL setText:[NSString stringWithFormat:@"Connex %@ Call",[typeOfCall capitalizedString]]];
                        
                        UIButton *declineBTN=(UIButton *)[_incomingCallView viewWithTag:8];
                        [CTGVC setRoundCornertoView:declineBTN withBorderColor:nil borderWidth:0.0f WithRadius:.5 dependsOnHeight:YES];
                        UIButton *acceptBTN=(UIButton *)[_incomingCallView viewWithTag:9];
                        [CTGVC setRoundCornertoView:acceptBTN withBorderColor:nil borderWidth:0.0f WithRadius:.5 dependsOnHeight:YES];
                        
                        [declineBTN addTarget:self action:@selector(callResponse:) forControlEvents:UIControlEventTouchUpInside];
                        [acceptBTN addTarget:self action:@selector(callResponse:) forControlEvents:UIControlEventTouchUpInside];
                        
                        
                        NSString *path = [[NSBundle mainBundle] pathForResource:@"iphone" ofType:@"caf"];
                        NSURL *url=[NSURL URLWithString:path];
                        AudioServicesCreateSystemSoundID((__bridge CFURLRef)[NSURL fileURLWithPath:path], &soundId);
                        AudioServicesCreateSystemSoundID((__bridge CFURLRef _Nonnull)(url), &soundId);
                        AudioServicesPlaySystemSound(soundId);
                        timeCount=0;
                        
                        _callerNumber = phoneNumber;
                        dispatch_async(dispatch_get_main_queue(), ^{
                            [_callTimer invalidate];
                            _callTimer = nil;
                            _callTimer=[NSTimer scheduledTimerWithTimeInterval:1.0 target:self selector:@selector(checkRingingTime:) userInfo:nil repeats:YES];
                        });
                    });
                    
                }
            }
        }
    }
}

// on tapping local notifications like missedCallNotification etc

- (void)application:(UIApplication *)app didReceiveLocalNotification:(UILocalNotification *)notif
{
    [[NSUserDefaults standardUserDefaults] setBool:YES forKey:@"isFromIdentifier"];
    navigation=(UINavigationController *)self.window.rootViewController;
    [[UIApplication sharedApplication]cancelAllLocalNotifications];
//    [self testAlert:notif];
    userInfoDic=[notif.userInfo mutableCopy];
    NSString *roomID=[[userInfoDic objectForKey:@"count_val"] objectForKey:@"roomId"];
    NSString *senderID=[[userInfoDic objectForKey:@"count_val"] objectForKey:@"sender_id"];
    callerName=[[userInfoDic objectForKey:@"count_val"] objectForKey:@"from_name"];
    NSString *typeOfCall=[[userInfoDic objectForKey:@"count_val"] objectForKey:@"mod"];
    NSString *phoneNumber = [[userInfoDic objectForKey:@"count_val"] objectForKey:@"phone"];
    
    callerName= [self fetchName:phoneNumber];
    
    NSString *status=[[NSUserDefaults standardUserDefaults]objectForKey:connexCallStatus];
    if (![status isEqualToString:connexCallStatusOnCall] || ![status isEqualToString:connexCallStatusRinging] || ![status isEqualToString:connexCallStatusCalling])
    {
        CNGlobalViewController *CTGVC=[[CNGlobalViewController alloc]init];
        if (![CTGVC isOnCall])
        {
            if ([[[NSUserDefaults standardUserDefaults]objectForKey:CNUSERID]length]>0)
            {
                if ([typeOfCall isEqualToString:@"voice"] || [typeOfCall isEqualToString:@"video"])
                {
//                    [self testAlert:@"1"];
                    if (![_endedMeetingID isEqualToString:roomID])
                    {
//                        [self testAlert:@"2"];
                        NSCalendar *c = [NSCalendar currentCalendar];
                        NSDate *d1 = [NSDate date];
                        NSDateFormatter *dateFormat = [[NSDateFormatter alloc]init];
                        [dateFormat setTimeZone:[NSTimeZone timeZoneWithAbbreviation:[[userInfoDic objectForKey:@"count_val"] objectForKey:@"server_timezone"]]];
                        [dateFormat setDateFormat:@"yyyy-MM-dd HH:mm:ss"];
                        NSDate *d2 = [dateFormat dateFromString:[[userInfoDic objectForKey:@"count_val"] objectForKey:@"server_datetime"]];//[NSDate dateWithTimeIntervalSince1970:[roomID intValue]];//2012-06-22
                        NSDateComponents *components = [c components:NSCalendarUnitHour|NSCalendarUnitMinute|NSCalendarUnitSecond fromDate:d2 toDate:d1 options:0];
                        NSLog(@"date d1 =%@ d2=%@",d1,d2);
                    
                        NSLog(@"hour %ld minute %ld second %ld",(long)components.hour,(long)components.minute,(long)components.second);
//                        [self testAlert:[NSString stringWithFormat:@"hour %ld minute %ld second %ld",(long)components.hour,(long)components.minute,(long)components.second]];
                        if (components.hour == 0 && (components.minute == 0||components.minute==1) && components.second < 60)
                        {
//                            [self testAlert:@"12"];
                            if ([[CTGVC checkStatusForMicrophoneAndCameraPermission]isEqualToString:@"OK"])
                            {
                                if ([typeOfCall isEqualToString:@"voice"])
                                    [CTGVC sendPushToUser:senderID withRoomID:roomID withCallType:@"voiceaccept" withStatus:@""];//callReceived
                                else if ([typeOfCall isEqualToString:@"video"])
                                    [CTGVC sendPushToUser:senderID withRoomID:roomID withCallType:@"videoaccept" withStatus:@""];//callReceived
                                [[NSUserDefaults standardUserDefaults]setObject:connexCallStatusOnCall forKey:connexCallStatus];
                                    [_dateFormatter setDateFormat:@"hh:mm a"];
                                NSDate *time = [_dateFormatter dateFromString:[_dateFormatter stringFromDate:[NSDate date]]];
                                [_dateFormatter setDateFormat:@"dd-MM-YYYY"];
                                NSString *date = [_dateFormatter stringFromDate:[NSDate date]];
                                [CTGVC addToCallLogWithName:callerName phone:phoneNumber typeOfCall:[NSNumber numberWithInt:2] time:time date:date fullDate:[NSDate date]];
                                
                                double delayInSeconds = 1.0;
                                dispatch_time_t popTime = dispatch_time(DISPATCH_TIME_NOW, (int64_t)(delayInSeconds * NSEC_PER_SEC));
                                dispatch_after(popTime, dispatch_get_main_queue(), ^(void){
                                    ARTCVideoChatViewController *ARTCVCVC;
                                    ARTCVCVC =[[UIStoryboard storyboardWithName:@"Video" bundle:nil]instantiateViewControllerWithIdentifier:@"ARTCVideoChatViewController"];
                                    ARTCVCVC.receiverID=senderID;
                                    ARTCVCVC.roomID = roomID;
                                    if ([typeOfCall isEqualToString:@"voice"])
                                        ARTCVCVC.typeOfCall = @"voiceaccept";
                                    else if ([typeOfCall isEqualToString:@"video"])
                                        ARTCVCVC.typeOfCall = @"videoaccept";
                                    ARTCVCVC.userName=callerName;
                                    ARTCVCVC.profileImageUrl=profilePicLink;
                                    CATransition *Transition=[CATransition animation];
                                    [Transition setDuration:0.1f];
                                    [Transition setTimingFunction:[CAMediaTimingFunction functionWithName:kCAMediaTimingFunctionEaseIn]];
                                    [Transition setType:kCAMediaTimingFunctionEaseIn];
                                    [[[navigation view] layer] addAnimation:Transition forKey:nil];
                                    [navigation pushViewController:ARTCVCVC animated:NO];
                                });
                            }
                            else
                            {
                                [CTGVC showAlertForCameraAndMicrophonePermissions];
                            }
                        }
                        else
                        {
                            [_dateFormatter setDateFormat:@"hh:mm a"];
                            NSDate *time = [_dateFormatter dateFromString:[_dateFormatter stringFromDate:[NSDate date]]];
                            [_dateFormatter setDateFormat:@"dd-MM-yyyy"];
                            NSString *date = [_dateFormatter stringFromDate:[NSDate date]];
                            [CTGVC addToCallLogWithName:callerName phone:phoneNumber typeOfCall:[NSNumber numberWithInt:0] time:time date:date fullDate:[NSDate date]];
                        }
                    }
                    else
                    {
                        double delayInSeconds = 3.0;
                        dispatch_time_t popTime = dispatch_time(DISPATCH_TIME_NOW, (int64_t)(delayInSeconds * NSEC_PER_SEC));
                        dispatch_after(popTime, dispatch_get_main_queue(), ^(void){
                            [self showAlertForCallDisconnect];
                        });
                    }
                }
                else if ([typeOfCall isEqualToString:@"callEnded"])
                {
                    [[NSUserDefaults standardUserDefaults]setObject:connexCallStatusIdle forKey:connexCallStatus];
                    NSLog(@"dismiss push");
                    _endedMeetingID=roomID;
                    NSLog(@"ended room id %@",_endedMeetingID);
                    [_dateFormatter setDateFormat:@"hh:mm a"];
                    NSDate *time = [_dateFormatter dateFromString:[_dateFormatter stringFromDate:[NSDate date]]];
                    [_dateFormatter setDateFormat:@"dd-MM-YYYY"];
                    NSString *date = [_dateFormatter stringFromDate:[NSDate date]];
                    [CTGVC addToCallLogWithName:callerName phone:phoneNumber typeOfCall:[NSNumber numberWithInt:0] time:time date:date fullDate:[NSDate date]];
                        
                    CNLogViewController *CNLVC=[[UIStoryboard storyboardWithName:@"IPhone" bundle:nil]instantiateViewControllerWithIdentifier:@"CNLogViewController"];
                    CATransition *Transition=[CATransition animation];
                    [Transition setDuration:0.1f];
                    [Transition setTimingFunction:[CAMediaTimingFunction functionWithName:kCAMediaTimingFunctionEaseIn]];
                    [Transition setType:kCAMediaTimingFunctionEaseIn];
                    [[[navigation view] layer] addAnimation:Transition forKey:nil];
                    [navigation pushViewController:CNLVC animated:NO];
                    //[self showAlertForCallDisconnect];
                }
            }
        }
        else
        {
            UIAlertController *alertController = [UIAlertController
                                                      alertControllerWithTitle:@"Information!!!"
                                                      message:[NSString stringWithFormat:@"You need to disconnect normal phone call before receiving connex call"]
                                                      preferredStyle:UIAlertControllerStyleAlert];
            UIAlertAction *okAction = [UIAlertAction
                                           actionWithTitle:NSLocalizedString(@"OK", @"OK action")
                                           style:UIAlertActionStyleDefault
                                           handler:^(UIAlertAction *action)
            {
                NSLog(@"OK action");
                // missed call entry
                [_dateFormatter setDateFormat:@"hh:mm a"];
                NSDate *time = [_dateFormatter dateFromString:[_dateFormatter stringFromDate:[NSDate date]]];
                [_dateFormatter setDateFormat:@"dd-MM-yyyy"];
                NSString *date = [_dateFormatter stringFromDate:[NSDate date]];
                [CTGVC addToCallLogWithName:callerName phone:phoneNumber typeOfCall:[NSNumber numberWithInt:0] time:time date:date fullDate:[NSDate date]];
                // send push to caller
                [CTGVC sendPushToUser:senderID withRoomID:roomID withCallType:@"reject" withStatus:@"reject"];
                
                [alertController dismissViewControllerAnimated:YES completion:nil];
            }];
            [alertController addAction:okAction];
            [navigation.visibleViewController presentViewController:alertController animated:YES completion:nil];
        }
    }
    else if ([navigation.visibleViewController isKindOfClass:[ARTCVideoChatViewController class]])
    {
        if ([typeOfCall isEqualToString:@"callEnded"])
        {
            [[NSNotificationCenter defaultCenter]postNotificationName:@"CallEndedByRemoteUser" object:nil];
        }
        else
        {
            dispatch_async(dispatch_get_main_queue(), ^{
                [self showThirdCallView];
                timeCount=0;
                thirdCallTimer=[NSTimer scheduledTimerWithTimeInterval:1.0f target:self selector:@selector(checkThirdCallRingingTime:) userInfo:nil repeats:YES];
            });
        }
    }
}

- (void)application:(UIApplication *)application handleActionWithIdentifier:(nullable NSString *)identifier forLocalNotification:(UILocalNotification *)notification completionHandler:(void(^)())completionHandler
{
    completionHandler(UIBackgroundFetchResultNewData);
    userInfoDic=[notification.userInfo mutableCopy];
    NSString *roomID=[[userInfoDic objectForKey:@"count_val"] objectForKey:@"roomId"];
    NSString *senderID=[[userInfoDic objectForKey:@"count_val"] objectForKey:@"sender_id"];
    callerName=[[userInfoDic objectForKey:@"count_val"] objectForKey:@"from_name"];
    NSString *callType=[[userInfoDic objectForKey:@"count_val"] objectForKey:@"mod"];
    
    NSString *phoneNumber = [[userInfoDic objectForKey:@"count_val"] objectForKey:@"phone"];
    callerName=[self fetchName:phoneNumber];
    
        if ([identifier isEqualToString:@"ACCEPT_IDENTIFIER"])
        {
            [[NSUserDefaults standardUserDefaults]setBool:YES forKey:@"isFromIdentifier"];
            NSString *status = [[NSUserDefaults standardUserDefaults]objectForKey:connexCallStatus];
            if ([status isEqualToString:connexCallStatusIdle])
            {
                if (![_endedMeetingID isEqualToString:roomID])
                {
                    
                    CNGlobalViewController *CTGVC=[[CNGlobalViewController alloc]init];
    
                    NSCalendar *c = [NSCalendar currentCalendar];
                    NSDate *d1 = [NSDate date];
                    NSDateFormatter *dateFormat = [[NSDateFormatter alloc]init];
                    [dateFormat setTimeZone:[NSTimeZone timeZoneWithAbbreviation:[[userInfoDic objectForKey:@"count_val"] objectForKey:@"server_timezone"]]];
                    [dateFormat setDateFormat:@"yyyy-MM-dd HH:mm:ss"];
                    NSDate *d2 = [dateFormat dateFromString:[[userInfoDic objectForKey:@"count_val"] objectForKey:@"server_datetime"]];//[NSDate dateWithTimeIntervalSince1970:[roomID intValue]];//2012-06-22
                    NSDateComponents *components = [c components:NSCalendarUnitHour|NSCalendarUnitMinute|NSCalendarUnitSecond fromDate:d2 toDate:d1 options:0];
                    NSLog(@"date d1 =%@ d2=%@",d1,d2);
    
                    NSLog(@"hour %ld minute %ld second %ld",(long)components.hour,(long)components.minute,(long)components.second);
//                    [self testAlert:[NSString stringWithFormat:@"hour %ld minute %ld second %ld",(long)components.hour,(long)components.minute,(long)components.second]];
                    if (components.hour == 0 && (components.minute == 0||components.minute==1) && components.second < 60)
                    {
                        if ([[CTGVC checkStatusForMicrophoneAndCameraPermission]isEqualToString:@"OK"])
                        {
                            
                            [[NSUserDefaults standardUserDefaults]setObject:connexCallStatusOnCall forKey:connexCallStatus];
                            [_dateFormatter setDateFormat:@"hh:mm a"];
                            NSDate *time = [_dateFormatter dateFromString:[_dateFormatter stringFromDate:[NSDate date]]];
                            [_dateFormatter setDateFormat:@"dd-MM-YYYY"];
                            NSString *date = [_dateFormatter stringFromDate:[NSDate date]];
                            [CTGVC addToCallLogWithName:callerName phone:phoneNumber typeOfCall:[NSNumber numberWithInt:2] time:time date:date fullDate:[NSDate date]];
    
                            if ([callType isEqualToString:@"voice"])
                                [CTGVC sendPushToUser:senderID withRoomID:roomID withCallType:@"voiceaccept" withStatus:@""];//callReceived
                            else if ([callType isEqualToString:@"video"])
                                [CTGVC sendPushToUser:senderID withRoomID:roomID withCallType:@"videoaccept" withStatus:@""];//callReceived
                            double delayInSeconds = 1.0;
                            dispatch_time_t popTime = dispatch_time(DISPATCH_TIME_NOW, (int64_t)(delayInSeconds * NSEC_PER_SEC));
                            dispatch_after(popTime, dispatch_get_main_queue(), ^(void){
                                navigation=(UINavigationController *)self.window.rootViewController;
                                ARTCVideoChatViewController *ARTCVCVC;
                                ARTCVCVC =[[UIStoryboard storyboardWithName:@"Video" bundle:nil]instantiateViewControllerWithIdentifier:@"ARTCVideoChatViewController"];
                                ARTCVCVC.roomID = roomID;
                                ARTCVCVC.receiverID = senderID;
                                if ([callType isEqualToString:@"voice"])
                                    ARTCVCVC.typeOfCall=@"voiceaccept";
                                else if ([callType isEqualToString:@"video"])
                                    ARTCVCVC.typeOfCall=@"videoaccept";
                                ARTCVCVC.userName=callerName;
                                ARTCVCVC.profileImageUrl=profilePicLink;
                                CATransition *Transition=[CATransition animation];
                                [Transition setDuration:0.1f];
                                [Transition setTimingFunction:[CAMediaTimingFunction functionWithName:kCAMediaTimingFunctionEaseIn]];
                                [Transition setType:kCAMediaTimingFunctionEaseIn];
                                [[[navigation view] layer] addAnimation:Transition forKey:nil];
                                [navigation pushViewController:ARTCVCVC animated:NO];
                            });
                        }
                        else
                        {
                            [CTGVC showAlertForCameraAndMicrophonePermissions];
                        }
                    }
                    else
                    {
                        [_dateFormatter setDateFormat:@"hh:mm a"];
                        NSDate *time = [_dateFormatter dateFromString:[_dateFormatter stringFromDate:[NSDate date]]];
                        [_dateFormatter setDateFormat:@"dd-MM-yyyy"];
                        NSString *date = [_dateFormatter stringFromDate:[NSDate date]];
                        [CTGVC addToCallLogWithName:callerName phone:phoneNumber typeOfCall:[NSNumber numberWithInt:0] time:time date:date fullDate:[NSDate date]];
                    }
                }
                else
                {
                    [self showAlertForCallDisconnect];
                }
            }
            else
            {
                dispatch_async(dispatch_get_main_queue(), ^{
                    [self showThirdCallView];
                    timeCount=0;
                    thirdCallTimer=[NSTimer scheduledTimerWithTimeInterval:1.0f target:self selector:@selector(checkThirdCallRingingTime:) userInfo:nil repeats:YES];
                });
            }
        }
        else if ([identifier isEqualToString:@"DENY_IDENTIFIER"])
        {
            [[UIApplication sharedApplication]cancelAllLocalNotifications];
            CNGlobalViewController *CTGVC=[[CNGlobalViewController alloc]init];
            [CTGVC sendPushToUser:senderID withRoomID:roomID withCallType:@"reject" withStatus:@"reject"];
            [_dateFormatter setDateFormat:@"hh:mm a"];
            NSDate *time = [_dateFormatter dateFromString:[_dateFormatter stringFromDate:[NSDate date]]];
            [_dateFormatter setDateFormat:@"dd-MM-YYYY"];
            NSString *date = [_dateFormatter stringFromDate:[NSDate date]];
            [CTGVC addToCallLogWithName:callerName phone:phoneNumber typeOfCall:[NSNumber numberWithInt:0] time:time date:date fullDate:[NSDate date]];
    
            NSLog(@"call rejected caller name %@",callerName);
            missedCallNotification = [[UILocalNotification alloc] init];
            missedCallNotification.fireDate = [NSDate dateWithTimeIntervalSinceNow:1];
            missedCallNotification.alertBody = [NSString stringWithFormat: @"You have missed a call from %@",[[userInfoDic objectForKey:@"count_val"] objectForKey:@"from_name"]];
            missedCallNotification.timeZone = [NSTimeZone localTimeZone];
            missedCallNotification.soundName=UILocalNotificationDefaultSoundName;
            [[UIApplication sharedApplication] scheduleLocalNotification:missedCallNotification];
        }
}

// rejecting or receiving calls

-(void)callResponse:(UIButton *)sender
{
    dispatch_async(dispatch_get_main_queue(), ^{
        [_callTimer invalidate];
        _callTimer = nil;
    });
    if (sender.tag == 8) // 8 for reject
    {
        [[NSUserDefaults standardUserDefaults]setObject:connexCallStatusIdle forKey:connexCallStatus];
        CNGlobalViewController *CTGVC=[[CNGlobalViewController alloc]init];
        [_dateFormatter setDateFormat:@"hh:mm a"];
        NSDate *time = [_dateFormatter dateFromString:[_dateFormatter stringFromDate:[NSDate date]]];
        [_dateFormatter setDateFormat:@"dd-MM-YYYY"];
        NSString *date = [_dateFormatter stringFromDate:[NSDate date]];
        [CTGVC addToCallLogWithName:callerName phone:[[userInfoDic objectForKey:@"count_val"]objectForKey:@"phone"] typeOfCall:[NSNumber numberWithInt:0] time:time date:date fullDate:[NSDate date]];
        
        [UIView animateWithDuration:0.1 animations:^{
            [_incomingCallView setAlpha:0.0];
        } completion:^(BOOL finished) {
            if (finished)
            {
                AudioServicesDisposeSystemSoundID(soundId);
                [_incomingCallView removeFromSuperview];
                _incomingCallView=nil;
                
                NSString *roomID=[[userInfoDic objectForKey:@"count_val"] objectForKey:@"roomId"];
                NSString *senderID=[[userInfoDic objectForKey:@"count_val"] objectForKey:@"sender_id"];
                //                NSString *callType=[[userInfoDic objectForKey:@"count_val"] objectForKey:@"mod"];
                
                CNGlobalViewController *CTGVC=[[CNGlobalViewController alloc]init];
                [CTGVC sendPushToUser:senderID withRoomID:roomID withCallType:@"reject" withStatus:@"reject"];
            }
        }];
    }
    else if (sender.tag == 9) // 9 for accept call
    {
        AudioServicesDisposeSystemSoundID(soundId);
        NSString *callMode=[[userInfoDic objectForKey:@"count_val"]objectForKey:@"mod"];
        NSString *roomID=[[userInfoDic objectForKey:@"count_val"] objectForKey:@"roomId"];
        NSString *senderID=[[userInfoDic objectForKey:@"count_val"] objectForKey:@"sender_id"];
        
        CNGlobalViewController *CTGVC=[[CNGlobalViewController alloc]init];
        
        [_dateFormatter setDateFormat:@"hh:mm a"];
        NSDate *time = [_dateFormatter dateFromString:[_dateFormatter stringFromDate:[NSDate date]]];
        [_dateFormatter setDateFormat:@"dd-MM-YYYY"];
        NSString *date = [_dateFormatter stringFromDate:[NSDate date]];
        [CTGVC addToCallLogWithName:callerName phone:[[userInfoDic objectForKey:@"count_val"]objectForKey:@"phone"] typeOfCall:[NSNumber numberWithInt:2] time:time date:date fullDate:[NSDate date]];
        
        [_incomingCallView removeFromSuperview];
        _incomingCallView = nil;
        
        if ([callMode isEqualToString:@"video"])
        {
            if ([[CTGVC checkStatusForMicrophoneAndCameraPermission]isEqualToString:@"OK"])
            {
                [[NSUserDefaults standardUserDefaults]setObject:connexCallStatusOnCall forKey:connexCallStatus];
                if ([callMode isEqualToString:@"voice"])
                    [CTGVC sendPushToUser:senderID withRoomID:roomID withCallType:@"voiceaccept" withStatus:@""];//callReceived
                else if ([callMode isEqualToString:@"video"])
                    [CTGVC sendPushToUser:senderID withRoomID:roomID withCallType:@"videoaccept" withStatus:@""];//callReceived
                
                ARTCVideoChatViewController *ARTCVCVC;
                ARTCVCVC =[[UIStoryboard storyboardWithName:@"Video" bundle:nil]instantiateViewControllerWithIdentifier:@"ARTCVideoChatViewController"];
                ARTCVCVC.roomID = roomID;
                ARTCVCVC.typeOfCall = @"videoaccept";
                ARTCVCVC.userName=callerName;
                ARTCVCVC.receiverID=senderID;
                ARTCVCVC.profileImageUrl=profilePicLink;
                CATransition *Transition=[CATransition animation];
                [Transition setDuration:0.1f];
                [Transition setTimingFunction:[CAMediaTimingFunction functionWithName:kCAMediaTimingFunctionEaseIn]];
                [Transition setType:kCAMediaTimingFunctionEaseIn];
                [[[navigation view] layer] addAnimation:Transition forKey:nil];
                [navigation pushViewController:ARTCVCVC animated:NO];
            }
            else
            {
                [[NSUserDefaults standardUserDefaults]setObject:connexCallStatusIdle forKey:connexCallStatus];
                [CTGVC sendPushToUser:senderID withRoomID:roomID withCallType:@"reject" withStatus:@"reject"];
                [CTGVC showAlertForCameraAndMicrophonePermissions];
            }
            
        }
        else if ([callMode isEqualToString:@"voice"])
        {
            if ([[CTGVC checkStatusForMicrophoneAndCameraPermission]isEqualToString:@"OK"])
            {
                [[NSUserDefaults standardUserDefaults]setObject:connexCallStatusOnCall forKey:connexCallStatus];
                if ([callMode isEqualToString:@"voice"])
                    [CTGVC sendPushToUser:senderID withRoomID:roomID withCallType:@"voiceaccept" withStatus:@""];//callReceived
                else if ([callMode isEqualToString:@"video"])
                    [CTGVC sendPushToUser:senderID withRoomID:roomID withCallType:@"videoaccept" withStatus:@""];//callReceived
                
                ARTCVideoChatViewController *ARTCVCVC;
                ARTCVCVC =[[UIStoryboard storyboardWithName:@"Video" bundle:nil]instantiateViewControllerWithIdentifier:@"ARTCVideoChatViewController"];
                ARTCVCVC.roomID = roomID;
                ARTCVCVC.typeOfCall = @"voiceaccept";
                ARTCVCVC.userName=callerName;
                ARTCVCVC.receiverID=senderID;
                ARTCVCVC.profileImageUrl=profilePicLink;
                CATransition *Transition=[CATransition animation];
                [Transition setDuration:0.1f];
                [Transition setTimingFunction:[CAMediaTimingFunction functionWithName:kCAMediaTimingFunctionEaseIn]];
                [Transition setType:kCAMediaTimingFunctionEaseIn];
                [[[navigation view] layer] addAnimation:Transition forKey:nil];
                [navigation pushViewController:ARTCVCVC animated:NO];
            }
            else
            {
                [[NSUserDefaults standardUserDefaults]setObject:connexCallStatusIdle forKey:connexCallStatus];
                [CTGVC sendPushToUser:senderID withRoomID:roomID withCallType:@"reject" withStatus:@"reject"];
                [CTGVC showAlertForCameraAndMicrophonePermissions];
            }
        }
    }
}

// showing third call view

-(void)showThirdCallView
{
    if (_thirdCallView == nil)
    {
        [_thirdCallView removeFromSuperview];
        _thirdCallView=nil;
        _thirdCallView=[[[NSBundle mainBundle]loadNibNamed:@"Dialler" owner:self options:nil]objectAtIndex:3];
        [_thirdCallView setFrame:CGRectMake(0, 0, FULLWIDTH, FULLHEIGHT)];
        [_thirdCallView setAlpha:0.0];
        [navigation.visibleViewController.view addSubview:_thirdCallView];
        [UIView animateWithDuration:0.2 animations:^{
            [_thirdCallView setAlpha:1.0];
        }];
        
        UIImageView *backImage=(UIImageView *)[_thirdCallView viewWithTag:1];
        [backImage setBackgroundColor:[UIColor clearColor]];
        if (profilePicLink != nil)
            [backImage setImage:[UIImage imageWithContentsOfFile:profilePicLink]];
        
        UILabel *nameLBL=(UILabel *)[_thirdCallView viewWithTag:2];
        [nameLBL setText:[callerName capitalizedString]];
        
        UIButton *declineBTN=(UIButton *)[_thirdCallView viewWithTag:8];
        
        UILabel *callTypeLBL = (UILabel *)[_thirdCallView viewWithTag:3];
        NSString *typeOfCall=[[_thirdCallDic objectForKey:@"count_val"] objectForKey:@"mod"];
        [callTypeLBL setText:[NSString stringWithFormat:@"Connex %@ Call",[typeOfCall capitalizedString]]];
        
        UIButton *acceptBTN=(UIButton *)[_thirdCallView viewWithTag:9];
        //    [CTGVC setRoundCornertoView:acceptBTN withBorderColor:nil borderWidth:0.0f WithRadius:.5 dependsOnHeight:YES];
        
        [declineBTN addTarget:self action:@selector(thirdCallResponse:) forControlEvents:UIControlEventTouchUpInside];
        [acceptBTN addTarget:self action:@selector(thirdCallResponse:) forControlEvents:UIControlEventTouchUpInside];
    }
}

//third call accept or reject

-(void)thirdCallResponse:(UIButton *)sender
{
    CNGlobalViewController *CTGVC=[[CNGlobalViewController alloc]init];
    NSString *roomID=[[_thirdCallDic objectForKey:@"count_val"] objectForKey:@"roomId"];
    NSString *typeOfCall=[[_thirdCallDic objectForKey:@"count_val"] objectForKey:@"mod"];
    _thirdCallerName=[[_thirdCallDic objectForKey:@"count_val"] objectForKey:@"from_name"];
    NSString *senderID=[[_thirdCallDic objectForKey:@"count_val"] objectForKey:@"sender_id"];
    NSString *phoneNumber = [[_thirdCallDic objectForKey:@"count_val"] objectForKey:@"phone"];
    
    _thirdCallerName = [self fetchName:phoneNumber];
    
    dispatch_async(dispatch_get_main_queue(), ^{
        [thirdCallTimer invalidate];
        thirdCallTimer = nil;
        [UIView animateWithDuration:0.1 animations:^{
            [_thirdCallView setAlpha:0.0];
        } completion:^(BOOL finished) {
            if (finished)
            {
                [_thirdCallView removeFromSuperview];
            }
        }];
    });
    if (sender.tag == 8) // 8 for end current call and accept incoming
    {
        //[self testAlert:senderID];
        [_dateFormatter setDateFormat:@"hh:mm a"];
        NSDate *time = [_dateFormatter dateFromString:[_dateFormatter stringFromDate:[NSDate date]]];
        [_dateFormatter setDateFormat:@"dd-MM-yyyy"];
        NSString *date = [_dateFormatter stringFromDate:[NSDate date]];
        [CTGVC addToCallLogWithName:_thirdCallerName phone:phoneNumber typeOfCall:[NSNumber numberWithInt:2] time:time date:date fullDate:[NSDate date]];
        
        if ([typeOfCall isEqualToString:@"voice"])
            [CTGVC sendPushToUser:senderID withRoomID:roomID withCallType:@"voiceaccept" withStatus:@""];//callReceived
        else if ([typeOfCall isEqualToString:@"video"])
            [CTGVC sendPushToUser:senderID withRoomID:roomID withCallType:@"videoaccept" withStatus:@""];//callReceived
        [[NSNotificationCenter defaultCenter]postNotificationName:@"DidReceiveCallOnWaitingMode" object:nil userInfo:_thirdCallDic];
    }
    else if (sender.tag == 9) // 9 for reject incoming
    {
        NSLog(@"OK action");
        [_dateFormatter setDateFormat:@"hh:mm a"];
        NSDate *time = [_dateFormatter dateFromString:[_dateFormatter stringFromDate:[NSDate date]]];
        [_dateFormatter setDateFormat:@"dd-MM-yyyy"];
        NSString *date = [_dateFormatter stringFromDate:[NSDate date]];
        [CTGVC addToCallLogWithName:_thirdCallerName phone:phoneNumber typeOfCall:[NSNumber numberWithInt:0] time:time date:date fullDate:[NSDate date]];
        // send push to caller
        [CTGVC sendPushToUser:senderID withRoomID:roomID withCallType:@"reject" withStatus:@"reject"];
    }
}

// alert for call disconnect by remote user

-(void)showAlertForCallDisconnect
{
    UIAlertController *alertController = [UIAlertController
                                          alertControllerWithTitle:@"Information!"
                                          message:[NSString stringWithFormat:@"The call has been disconnected by %@.",callerName]
                                          preferredStyle:UIAlertControllerStyleAlert];
    UIAlertAction *okAction = [UIAlertAction
                               actionWithTitle:NSLocalizedString(@"OK", @"OK action")
                               style:UIAlertActionStyleCancel
                               handler:^(UIAlertAction *action)
                               {
                               }];
    [alertController addAction:okAction];
    [navigation.visibleViewController presentViewController:alertController animated:YES completion:nil];
}

-(void)testAlert:(id)message
{
    double delayInSeconds = 2.0;
    dispatch_time_t popTime = dispatch_time(DISPATCH_TIME_NOW, (int64_t)(delayInSeconds * NSEC_PER_SEC));
    dispatch_after(popTime, dispatch_get_main_queue(), ^(void){
        UIAlertController *alertController = [UIAlertController
                                              alertControllerWithTitle:@"Information!"
                                              message:[NSString stringWithFormat:@"%@",message]
                                              preferredStyle:UIAlertControllerStyleAlert];
        UIAlertAction *okAction = [UIAlertAction
                                   actionWithTitle:NSLocalizedString(@"OK", @"OK action")
                                   style:UIAlertActionStyleCancel
                                   handler:^(UIAlertAction *action)
                                   {
                                   }];
        [alertController addAction:okAction];
        [navigation.visibleViewController presentViewController:alertController animated:YES completion:nil];
    });
}

- (void)applicationWillResignActive:(UIApplication *)application {
    // Sent when the application is about to move from active to inactive state. This can occur for certain types of temporary interruptions (such as an incoming phone call or SMS message) or when the user quits the application and it begins the transition to the background state.
    // Use this method to pause ongoing tasks, disable timers, and throttle down OpenGL ES frame rates. Games should use this method to pause the game.
}

- (void)applicationDidEnterBackground:(UIApplication *)application
{
//    [self testAlert:@"On call 1"];
//    _isUpdateNotificationReceived=NO;
//    CNGlobalViewController *CTGVC=[[CNGlobalViewController alloc]init];
//    BOOL isThereAnyCall = [CTGVC isOnCall];
//    if (isThereAnyCall)
//    {
//        [self testAlert:@"On call 2"];
//    }
    // Use this method to release shared resources, save user data, invalidate timers, and store enough application state information to restore your application to its current state in case it is terminated later.
    // If your application supports background execution, this method is called instead of applicationWillTerminate: when the user quits.
}



- (void)applicationWillEnterForeground:(UIApplication *)application
{
    [[UIApplication sharedApplication] setApplicationIconBadgeNumber:-1];
    [[UIApplication sharedApplication] setApplicationIconBadgeNumber:0];
    [[NSUserDefaults standardUserDefaults]setObject:[NSString stringWithFormat:@"0"] forKey:CNBADGECOUNT];
//    [self testAlert1:[[NSUserDefaults standardUserDefaults] boolForKey:@"isFromIdentifier"]?@"YES":@"NO"];
//    if ([[NSUserDefaults standardUserDefaults] boolForKey:@"isFromIdentifier"]== NO)
//    {
//        double delayInSeconds = 1.0;
//        dispatch_time_t popTime = dispatch_time(DISPATCH_TIME_NOW, (int64_t)(delayInSeconds * NSEC_PER_SEC));
//        dispatch_after(popTime, dispatch_get_main_queue(), ^(void){
//            NSString *callMode=[[remoteNotif objectForKey:@"count_val"]objectForKey:@"mod"];
//            userInfoDic=[remoteNotif mutableCopy];
//            callerName=[[userInfoDic objectForKey:@"count_val"] objectForKey:@"from_name"];
//            NSString *phoneNumber = [[userInfoDic objectForKey:@"count_val"] objectForKey:@"phone"];
//            NSManagedObject *user;
//            NSFetchRequest *fetchRequest=[NSFetchRequest fetchRequestWithEntityName:@"ContactList"];
//            NSArray *arr=[[self.managedObjectContext executeFetchRequest:fetchRequest error:nil] mutableCopy];
//            for (int i=0 ; i<arr.count ; i++)
//            {
//                NSManagedObject *obj=[arr objectAtIndex:i];
//                NSArray *phoneArray = [[obj valueForKey:@"phone"]mutableCopy];
//                for (NSString *str in phoneArray)
//                {
//                    if ([str rangeOfString:[NSString stringWithFormat:@"%@",phoneNumber]].location != NSNotFound)
//                    {
//                        user = obj;
//                        break;
//                    }
//                    break;
//                }
//            }
//            
//            if (user != nil || [user isKindOfClass:[NSNull class]])
//            {
//                callerName = [user valueForKey:@"name"];
//            }
//            else
//                callerName = phoneNumber;
//            
//            if ([callMode isEqualToString:@"voice"] || [callMode isEqualToString:@"video"])
//            {
//                CNGlobalViewController *CTGVC=[[CNGlobalViewController alloc]init];
//                [_incomingCallView removeFromSuperview];
//                _incomingCallView=nil;
//                _incomingCallView=[[[NSBundle mainBundle]loadNibNamed:@"Dialler" owner:self options:nil]objectAtIndex:0];
//                [_incomingCallView setFrame:CGRectMake(0, 0, FULLWIDTH, FULLHEIGHT)];
//                [_incomingCallView setAlpha:0.0];
//                [navigation.visibleViewController.view addSubview:_incomingCallView];
//                [UIView animateWithDuration:0.2 animations:^{
//                    [_incomingCallView setAlpha:1.0];
//                }];
//                
//                UILabel *nameLBL=(UILabel *)[_incomingCallView viewWithTag:2];
//                [nameLBL setText:[callerName capitalizedString]];
//                
//                UIButton *declineBTN=(UIButton *)[_incomingCallView viewWithTag:8];
//                [CTGVC setRoundCornertoView:declineBTN withBorderColor:nil borderWidth:0.0f WithRadius:.5 dependsOnHeight:YES];
//                UIButton *acceptBTN=(UIButton *)[_incomingCallView viewWithTag:9];
//                [CTGVC setRoundCornertoView:acceptBTN withBorderColor:nil borderWidth:0.0f WithRadius:.5 dependsOnHeight:YES];
//                
//                [declineBTN addTarget:self action:@selector(callResponse:) forControlEvents:UIControlEventTouchUpInside];
//                [acceptBTN addTarget:self action:@selector(callResponse:) forControlEvents:UIControlEventTouchUpInside];
//                
//                NSString *path = [[NSBundle mainBundle] pathForResource:@"iphone" ofType:@"caf"];
//                NSURL *url=[NSURL URLWithString:path];
//                AudioServicesCreateSystemSoundID((__bridge CFURLRef)[NSURL fileURLWithPath:path], &soundId);
//                AudioServicesCreateSystemSoundID((__bridge CFURLRef _Nonnull)(url), &soundId);
//                AudioServicesPlaySystemSound(soundId);
//                
//            }
//        });
//    }
    // Called as part of the transition from the background to the inactive state; here you can undo many of the changes made on entering the background.
}

- (void)applicationDidBecomeActive:(UIApplication *)application {
    
    // Restart any tasks that were paused (or not yet started) while the application was inactive. If the application was previously in the background, optionally refresh the user interface.
}

- (void)applicationWillTerminate:(UIApplication *)application {
    _isUpdateNotificationReceived=NO;
    // Called when the application is about to terminate. Save data if appropriate. See also applicationDidEnterBackground:.
    // Saves changes in the application's managed object context before the application terminates.
    [self saveContext];
}

#pragma mark - Core Data stack

@synthesize managedObjectContext = _managedObjectContext;
@synthesize managedObjectModel = _managedObjectModel;
@synthesize persistentStoreCoordinator = _persistentStoreCoordinator;

- (NSURL *)applicationDocumentsDirectory {
    // The directory the application uses to store the Core Data store file. This code uses a directory named "com.esolz.Connex_New" in the application's documents directory.
    return [[[NSFileManager defaultManager] URLsForDirectory:NSDocumentDirectory inDomains:NSUserDomainMask] lastObject];
}

- (NSManagedObjectModel *)managedObjectModel {
    // The managed object model for the application. It is a fatal error for the application not to be able to find and load its model.
    if (_managedObjectModel != nil) {
        return _managedObjectModel;
    }
    NSURL *modelURL = [[NSBundle mainBundle] URLForResource:@"Connex" withExtension:@"momd"];
    _managedObjectModel = [[NSManagedObjectModel alloc] initWithContentsOfURL:modelURL];
    return _managedObjectModel;
}

- (NSPersistentStoreCoordinator *)persistentStoreCoordinator {
    // The persistent store coordinator for the application. This implementation creates and returns a coordinator, having added the store for the application to it.
    if (_persistentStoreCoordinator != nil) {
        return _persistentStoreCoordinator;
    }
    
    // Create the coordinator and store
    
    _persistentStoreCoordinator = [[NSPersistentStoreCoordinator alloc] initWithManagedObjectModel:[self managedObjectModel]];
    NSURL *storeURL = [[self applicationDocumentsDirectory] URLByAppendingPathComponent:@"Connex_New.sqlite"];
    NSError *error = nil;
    NSString *failureReason = @"There was an error creating or loading the application's saved data.";
    if (![_persistentStoreCoordinator addPersistentStoreWithType:NSSQLiteStoreType configuration:nil URL:storeURL options:nil error:&error]) {
        // Report any error we got.
        NSMutableDictionary *dict = [NSMutableDictionary dictionary];
        dict[NSLocalizedDescriptionKey] = @"Failed to initialize the application's saved data";
        dict[NSLocalizedFailureReasonErrorKey] = failureReason;
        dict[NSUnderlyingErrorKey] = error;
        error = [NSError errorWithDomain:@"YOUR_ERROR_DOMAIN" code:9999 userInfo:dict];
        // Replace this with code to handle the error appropriately.
        // abort() causes the application to generate a crash log and terminate. You should not use this function in a shipping application, although it may be useful during development.
        NSLog(@"Unresolved error %@, %@", error, [error userInfo]);
        abort();
    }
    
    return _persistentStoreCoordinator;
}


- (NSManagedObjectContext *)managedObjectContext {
    // Returns the managed object context for the application (which is already bound to the persistent store coordinator for the application.)
    if (_managedObjectContext != nil) {
        return _managedObjectContext;
    }
    
    NSPersistentStoreCoordinator *coordinator = [self persistentStoreCoordinator];
    if (!coordinator) {
        return nil;
    }
    _managedObjectContext = [[NSManagedObjectContext alloc] initWithConcurrencyType:NSMainQueueConcurrencyType];
    [_managedObjectContext setPersistentStoreCoordinator:coordinator];
    return _managedObjectContext;
}

#pragma mark - Core Data Saving support

- (void)saveContext {
    NSManagedObjectContext *managedObjectContext = self.managedObjectContext;
    if (managedObjectContext != nil) {
        NSError *error = nil;
        if ([managedObjectContext hasChanges] && ![managedObjectContext save:&error]) {
            // Replace this implementation with code to handle the error appropriately.
            // abort() causes the application to generate a crash log and terminate. You should not use this function in a shipping application, although it may be useful during development.
            NSLog(@"Unresolved error %@, %@", error, [error userInfo]);
            abort();
        }
    }
}


#pragma mark -- Timer Work -- ///
// timer work for checking ringing time . currently RINGINGTIME set to 15

-(void)checkRingingTime:(NSTimer *)timer
{
    //    NSLog(@"timer %@",timer.description);
    timeCount+=1;
    NSLog(@"time %d",timeCount);
    if (timeCount == RINGINGTIME)
    {
        dispatch_async(dispatch_get_main_queue(), ^{
            [[NSUserDefaults standardUserDefaults]setObject:connexCallStatusIdle forKey:connexCallStatus];
            [_callTimer invalidate];
            _callTimer = nil;
            [UIView animateWithDuration:0.1 animations:^{
                [_incomingCallView setAlpha:0.0];
            } completion:^(BOOL finished) {
                if (finished)
                {
                    //[[UIApplication sharedApplication]cancelLocalNotification:callNotification];
                    
                    CNGlobalViewController *CTGVC=[[CNGlobalViewController alloc]init];
                    NSString *phoneNumber;
                    if (userInfoDic != nil)
                        phoneNumber = [[userInfoDic objectForKey:@"count_val"] objectForKey:@"phone"];
                    else
                        phoneNumber = [[remoteNotif objectForKey:@"count_val"] objectForKey:@"phone"];
                    [_dateFormatter setDateFormat:@"hh:mm a"];
                    NSDate *time = [_dateFormatter dateFromString:[_dateFormatter stringFromDate:[NSDate date]]];
                    [_dateFormatter setDateFormat:@"dd-MM-yyyy"];
                    NSString *date = [_dateFormatter stringFromDate:[NSDate date]];
                    [CTGVC addToCallLogWithName:callerName phone:phoneNumber typeOfCall:[NSNumber numberWithInt:0] time:time date:date fullDate:[NSDate date]];
                    
                    AudioServicesDisposeSystemSoundID(soundId);
                    [_incomingCallView removeFromSuperview];
                    _incomingCallView=nil;
                }
            }];
        });
    }
}

// ringing time / showing third call view for 15 secnds

-(void)checkThirdCallRingingTime:(NSTimer *)timer
{
    timeCount+=1;
    NSLog(@"time %d",timeCount);
    if (timeCount == RINGINGTIME)
    {
        dispatch_async(dispatch_get_main_queue(), ^{
            [thirdCallTimer invalidate];
            thirdCallTimer = nil;
            [UIView animateWithDuration:0.1 animations:^{
                [_thirdCallView setAlpha:0.0];
            } completion:^(BOOL finished) {
                if (finished)
                {
                    CNGlobalViewController *CTGVC=[[CNGlobalViewController alloc]init];
                    NSString *phoneNumber;
                    phoneNumber = [[_thirdCallDic objectForKey:@"count_val"] objectForKey:@"phone"];
                    [_dateFormatter setDateFormat:@"hh:mm a"];
                    NSDate *time = [_dateFormatter dateFromString:[_dateFormatter stringFromDate:[NSDate date]]];
                    [_dateFormatter setDateFormat:@"dd-MM-yyyy"];
                    NSString *date = [_dateFormatter stringFromDate:[NSDate date]];
                    [CTGVC addToCallLogWithName:callerName phone:phoneNumber typeOfCall:[NSNumber numberWithInt:0] time:time date:date fullDate:[NSDate date]];
                    [_thirdCallView removeFromSuperview];
                    _thirdCallView=nil;
                }
            }];
        });
    }
}

#pragma mark -- Contact syncing works here -- 
// background contact syncing
-(void)fetchContact
{
    [[NSUserDefaults standardUserDefaults]setObject:@"Syncing" forKey:IsSyncCompleted];
    [[NSUserDefaults standardUserDefaults]setObject:@"Syncing" forKey:IsUrlSyncCompleted];
    dispatch_async(dispatch_get_global_queue(DISPATCH_QUEUE_PRIORITY_DEFAULT, 0), ^{
        [self syncContact:^(BOOL finished) {
            if (finished) {
                dispatch_async(dispatch_get_main_queue(), ^{
                    
                });
            }
        }];
    });
}

-(void)syncContact:(completionBlock)comBlock
{
    groupsOfContact = [@[] mutableCopy]; //init a mutable array
    
    
    //In iOS 9 and above, use Contacts.framework
    if (NSClassFromString(@"CNContactStore")) { //if Contacts.framework is available
        contactStore = [[CNContactStore alloc] init]; //init a contactStore object
        
        //Check contacts authorization status using Contacts.framework entity
        switch ([CNContactStore authorizationStatusForEntityType:CNEntityTypeContacts]) {
                
            case CNAuthorizationStatusNotDetermined: { //Address book status not determined.
                
                [contactStore requestAccessForEntityType:CNEntityTypeContacts completionHandler:^(BOOL granted, NSError *error) { //permission Request alert will show here.
                    if (granted) { //if user allow to access a contacts in this app.
                        NSLog(@"1111111111");
                        [self fetchContactsFromContactsFrameWork]; //access contacts
                        comBlock(YES);
                    }
                    else
                    { // else ask to get a permission to access a contacts in this app.
//                        [self getPermissionToUser]; //Ask permission to user
                    }
                }];
            }
                break;
            case CNAuthorizationStatusAuthorized: { //Contact access permission is already authorized.
                NSLog(@"2222222222");
                
                [self fetchContactsFromContactsFrameWork]; //access contacts
                comBlock(YES);
            }
                break;
            default: { //else ask permission to user
                NSLog(@"3333333333");
//                [self getPermissionToUser];
            }
                break;
        }
        
    }
}

#pragma mark - Contacts.framework method
// fetching contacts via contact framework
- (void)fetchContactsFromContactsFrameWork { //access contacts using contacts.framework
    contactArray =[NSMutableArray new];
    NSArray *keyToFetch = @[CNContactEmailAddressesKey,CNContactFamilyNameKey,CNContactGivenNameKey,CNContactPhoneNumbersKey,CNContactPostalAddressesKey,CNContactImageDataKey,CNContactImageDataAvailableKey,CNContactUrlAddressesKey]; //contacts list key params to access using contacts.framework
    
    CNContactFetchRequest *fetchRequest = [[CNContactFetchRequest alloc] initWithKeysToFetch:keyToFetch]; //Contacts fetch request parrams object allocation
    
    [contactStore enumerateContactsWithFetchRequest:fetchRequest error:nil usingBlock:^(CNContact * _Nonnull contact, BOOL * _Nonnull stop) {
        [groupsOfContact addObject:contact]; //add objects of all contacts list in array
    }];
    
    NSCharacterSet *notAllowedChars = [[NSCharacterSet characterSetWithCharactersInString:@"abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890 "] invertedSet];
    
    //generate a custom dictionary to access
    for (CNContact *contact in groupsOfContact)
    {
        //        NSArray *thisOne = [[contact.phoneNumbers valueForKey:@"value"] valueForKey:@"digits"];
        //   [phoneNumberArray addObjectsFromArray:thisOne];
        //  NSLog(@"contact identifier: %@",contact.identifier);
        
        NSLog(@"name : %@ number count %ld",[NSString stringWithFormat:@"%@",contact.givenName],contact.phoneNumbers.count);
        if ([[NSString stringWithFormat:@"%@ %@",contact.givenName,contact.familyName] length]>1 && [contact.phoneNumbers count]>0 && !([[NSString stringWithFormat:@"%@",contact.givenName] isEqualToString:@"Identified As Spam"] || [[NSString stringWithFormat:@"%@",contact.familyName] isEqualToString:@"Identified as Spam"]))
        {
            NSMutableArray *phone=[NSMutableArray new];
            for (CNLabeledValue *label in contact.phoneNumbers)
            {
                NSString *phoneStr = [[label value] valueForKey:@"digits"];
                if ([phoneStr length]>1)
                    [phone addObject:phoneStr];
            }
                if ([phone count] > 0)
                {
                    NSMutableDictionary *mutDic=[NSMutableDictionary new];
                    [mutDic setObject:[[[NSString stringWithFormat:@"%@ %@",contact.givenName,contact.familyName] componentsSeparatedByCharactersInSet:notAllowedChars] componentsJoinedByString:@""] forKey:@"name"];
                    [mutDic setObject:[[[NSString stringWithFormat:@"%@",contact.givenName]componentsSeparatedByCharactersInSet:notAllowedChars] componentsJoinedByString:@""] forKey:@"firstName"];
                    [mutDic setObject:[[[NSString stringWithFormat:@"%@",contact.familyName]componentsSeparatedByCharactersInSet:notAllowedChars] componentsJoinedByString:@""] forKey:@"lastName"];
                    [mutDic setObject:phone forKey:@"phone"];
                    if ([contact imageDataAvailable])
                    {
                        NSString *imagePath = [[NSSearchPathForDirectoriesInDomains(NSDocumentDirectory, NSUserDomainMask, YES) objectAtIndex:0] stringByAppendingPathComponent:[NSString stringWithFormat:@"/pic%@.png",contact.identifier]];
                        [contact.imageData writeToFile:imagePath atomically:YES];
                        [mutDic setObject:imagePath forKey:@"image"];
                    }
                    else
                        [mutDic setObject:@"" forKey:@"image"];
                    
                    NSMutableArray *emailArray=[NSMutableArray new];
                    for (CNLabeledValue *label in contact.emailAddresses)
                    {
                        NSString *email = [label value];
                        if ([email length] > 0)
                        {
                            [emailArray addObject:email];
                        }
                    }
                    [mutDic setObject:emailArray forKey:@"email"];
                    NSMutableArray *postalAddress=[NSMutableArray new];
                    if ([contact postalAddresses] != nil)
                        postalAddress = [contact.postalAddresses mutableCopy];
                    [mutDic setObject:contact.identifier forKey:@"id"];
                    
                    NSMutableString *addressString = [[NSMutableString alloc]init];
                    if ([postalAddress count]>0)
                    {
                        CNLabeledValue *addLBLValue = postalAddress[0];
                        NSLog(@"addres value %@",[[addLBLValue value] valueForKey:@"street"]);
                        (![[[addLBLValue value] valueForKey:@"street"] isEqual:@""] && ![[[addLBLValue value] valueForKey:@"street"]isKindOfClass:[NSNull class]] && [[[addLBLValue value] valueForKey:@"street"]length]>0 && ![[[addLBLValue value] valueForKey:@"street"] isEqual:@"(null)"])?[addressString appendString:[NSString stringWithFormat:@"%@\n",[[[addLBLValue value] valueForKey:@"street"] stringByTrimmingCharactersInSet:[NSCharacterSet newlineCharacterSet]]]]:[addressString appendString:@""];
                        
                        (![[[addLBLValue value] valueForKey:@"city"] isEqual:@""] && ![[[addLBLValue value] valueForKey:@"city"]isKindOfClass:[NSNull class]] &&[[[addLBLValue value] valueForKey:@"city"]length]>0 && ![[[addLBLValue value] valueForKey:@"city"] isEqual:@"(null)"])?[addressString appendString:[NSString stringWithFormat:@"%@\n",[[addLBLValue value] valueForKey:@"city"]]]:[addressString appendString:@""];
                        
                        (![[[addLBLValue value] valueForKey:@"state"] isEqual:@""] && ![[[addLBLValue value] valueForKey:@"state"]isKindOfClass:[NSNull class]] &&[[[addLBLValue value] valueForKey:@"state"]length]>0 && ![[[addLBLValue value] valueForKey:@"state"] isEqual:@"(null)"])?[addressString appendString:[NSString stringWithFormat:@"%@ ",[[addLBLValue value] valueForKey:@"state"]]]:[addressString appendString:@""];
                        
                        (![[[addLBLValue value] valueForKey:@"postalCode"] isEqual:@""] && ![[[addLBLValue value] valueForKey:@"postalCode"]isKindOfClass:[NSNull class]] && [[[addLBLValue value] valueForKey:@"postalCode"]length]>0 && ![[[addLBLValue value] valueForKey:@"postalCode"] isEqual:@"(null)"])?[addressString appendString:[NSString stringWithFormat:@"%@\n",[[addLBLValue value] valueForKey:@"postalCode"]]]:[addressString appendString:@""];
                        
                        (![[[addLBLValue value] valueForKey:@"country"] isEqual:@""] && ![[[addLBLValue value] valueForKey:@"country"]isKindOfClass:[NSNull class]] && [[[addLBLValue value] valueForKey:@"country"]length]>0 && ![[[addLBLValue value] valueForKey:@"country"] isEqual:@"(null)"])?[addressString appendString:[NSString stringWithFormat:@"%@",[[addLBLValue value] valueForKey:@"country"]]]:[addressString appendString:@""];
                        
                    }
                    [mutDic setObject:addressString forKey:@"postalAddress"];
                    [mutDic setObject:@"" forKey:@"urlAddress"];
                    [contactArray addObject:mutDic];
                }
//            }
        }
    }
    [self syncToLocalDB:contactArray];
    [self syncToWeb];
    NSLog(@"total array: %@",contactArray);
}

-(void)syncToWeb
{
    finalContactArray = [[NSMutableArray alloc]init];
    if ([contactArray count]>kNumberOfContact)
    {
        NSMutableArray *partArray=[[NSMutableArray alloc]init];
        numberOfIteration=(int)([contactArray count]/kNumberOfContact);
        if ([contactArray count] % kNumberOfContact>0)
            numberOfIteration=numberOfIteration+1;
        
        DebugLog(@"numberofiteratons: %d", numberOfIteration);
        
        
        for (int iteration=0; iteration<numberOfIteration; iteration++)
        {
            partArray = [[NSMutableArray alloc]init];
            
            if (iteration==numberOfIteration-1)
            {
                NSArray *tempArray=[contactArray subarrayWithRange:NSMakeRange(iteration*kNumberOfContact, [contactArray count]-(kNumberOfContact*iteration))];
                [partArray addObjectsFromArray:tempArray];
            }
            else
            {
                NSArray *tempArray=[contactArray subarrayWithRange:NSMakeRange(iteration*kNumberOfContact, kNumberOfContact-1)];
                [partArray addObjectsFromArray:tempArray];
            }
            
            NSError *error=nil;
            NSData *jsonData2 = [NSJSONSerialization dataWithJSONObject:(NSArray *)partArray options:NSJSONWritingPrettyPrinted error:&error];
            NSString *jsonString = [[NSString alloc] initWithData:jsonData2 encoding:NSUTF8StringEncoding];
            NSString *encodedText = [self encodeToPercentEscapeString:jsonString];
            NSString *urlString1 =[NSString stringWithFormat:@"%@/connex_user_sync?",DOMAINURL];
            
            NSMutableURLRequest *request = [NSMutableURLRequest requestWithURL:[NSURL URLWithString:urlString1]];
            
            NSString *params = [[NSString alloc] initWithFormat:@"details_info=%@",jsonString];
//            DebugLog(@"url for domain: %@%@",urlString1,params);
            [request setHTTPMethod:@"POST"];
            [request setHTTPBody:[params dataUsingEncoding:NSUTF8StringEncoding]];
            [request setValue:@"application/x-www-form-urlencoded" forHTTPHeaderField:@"Content-Type"];
            NSError *error1;
            NSURLResponse *response;
            NSData *result = [NSURLConnection sendSynchronousRequest:request returningResponse:&response error:&error1];
            NSString* newStr = [[NSString alloc] initWithData:result encoding:NSUTF8StringEncoding];
            
            
            
            NSMutableDictionary *returnDic=[NSJSONSerialization JSONObjectWithData:result options:NSJSONReadingAllowFragments error:&error1];
//            NSLog(@"dictionary %@",returnDic);
            if (returnDic != nil && error1 == nil && [returnDic[@"response"]isEqualToString:@"TRUE"])
            {
                NSLog(@"true");
                if ([returnDic[@"all_connex"]count]>0)
                {
                    [finalContactArray addObjectsFromArray:returnDic[@"all_connex"]];
                }
            }
            else
            {
                DebugLog(@"newstr: %@",newStr);
                NSLog(@"error %@ -- %@",response,jsonString);
            }
            
        }
    }
    else
    {
        NSError *error=nil;
        NSError *saveError=nil;
        NSData *jsonData2 = [NSJSONSerialization dataWithJSONObject:(NSArray *)contactArray options:NSJSONWritingPrettyPrinted error:&error];
        NSString *jsonString = [[NSString alloc] initWithData:jsonData2 encoding:NSUTF8StringEncoding];
        
        NSString *encodedText = [self encodeToPercentEscapeString:jsonString];
        
//        DebugLog(@"json string subir: %@",encodedText);
        
        if ([contactArray count] == 0)
        {
            encodedText = @"{}";
        }
        
        NSString *urlString1 =[NSString stringWithFormat:@"%@/connex_user_sync?",DOMAINURL];
        
        NSMutableURLRequest *request = [NSMutableURLRequest requestWithURL:[NSURL URLWithString:urlString1]];
        
        NSString *params = [[NSString alloc] initWithFormat:@"details_info=%@",encodedText];
//        DebugLog(@"url for domain: %@%@",urlString1,params);
        [request setHTTPMethod:@"POST"];
        [request setHTTPBody:[params dataUsingEncoding:NSUTF8StringEncoding]];
        [request setValue:@"application/x-www-form-urlencoded" forHTTPHeaderField:@"Content-Type"];
        NSError *error1;
        NSURLResponse *response;
        NSData *result = [NSURLConnection sendSynchronousRequest:request returningResponse:&response error:&error1];
        NSString* newStr = [[NSString alloc] initWithData:result encoding:NSUTF8StringEncoding];
//        DebugLog(@"newstr: %@",newStr);
        
        NSMutableDictionary *returnDic=[NSJSONSerialization JSONObjectWithData:result options:NSJSONReadingAllowFragments error:&error1];
        NSLog(@"dictionary 1111%@",returnDic);
        if (returnDic != nil && error1 == nil && [returnDic[@"response"]isEqualToString:@"TRUE"])
        {
            if ([returnDic[@"all_connex"]count]>0)
            {
                [finalContactArray addObjectsFromArray:returnDic[@"all_connex"]];
            }
        }
    }
    [[NSUserDefaults standardUserDefaults]setObject:@"SyncCompleted" forKey:IsUrlSyncCompleted];
    [self syncToLocalDB:finalContactArray];
}

-(NSString *)encodeToPercentEscapeString:(NSString *)string
{
    return [string stringByAddingPercentEncodingWithAllowedCharacters:[NSCharacterSet URLQueryAllowedCharacterSet]];
}

// sync contacts to local db
-(void)syncToLocalDB:(NSMutableArray *)contacts
{
    [self resetCoreData];
    @try
    {
        NSManagedObjectContext *managedObjectContext = [self managedObjectContext];
        
        [managedObjectContext performBlock:^{
            
            NSFetchRequest *fetchRequest = [[NSFetchRequest alloc] initWithEntityName:@"ContactList"];
            NSError *error = nil;
            NSUInteger count = [managedObjectContext countForFetchRequest:fetchRequest
                                                                    error:&error];
            if (count>0) {
                NSArray *list = [[managedObjectContext executeFetchRequest:fetchRequest error:nil] mutableCopy];
                NSLog(@"number of list : %d",(int)list.count);
            }
            else
            {
                [self saveContact:contacts];
            }
        }];
    }
    @catch (NSException *exception) {
        
    }
    @finally
    {
    }
}
// saving all contacts to local db
-(void)saveContact:(NSMutableArray *)contactsArr
{
    for (int i=0; i< contactsArr.count; i++)
    {
        NSDictionary *mutDic = contactsArr[i];
        @try
        {
            // * to save to core data *
            NSManagedObjectContext *managedObjectContext = [self managedObjectContext];
            
            [managedObjectContext performBlock:^{
                
                // Create a new managed object
                
                NSManagedObject *newObject = [NSEntityDescription insertNewObjectForEntityForName:@"ContactList" inManagedObjectContext:managedObjectContext];
                [newObject setValue:[mutDic objectForKey:@"name"] forKey:@"name"];
                [newObject setValue:[mutDic objectForKey:@"firstName"] forKey:@"firstName"];
                [newObject setValue:[mutDic objectForKey:@"lastName"] forKey:@"lastName"];
                [newObject setValue:[mutDic objectForKey:@"email"] forKey:@"email"];
                [newObject setValue:[mutDic objectForKey:@"phone"] forKey:@"phone"];
                [newObject setValue:[mutDic objectForKey:@"id"] forKey:@"id"];
                [newObject setValue:[mutDic objectForKey:@"image"] forKey:@"image"];
                [newObject setValue:[mutDic objectForKey:@"postalAddress"] forKey:@"postalAddress"];
                if ([mutDic[@"connex"] isEqualToString:@"YES"])
                {
                    NSFetchRequest *fetchRequest=[NSFetchRequest fetchRequestWithEntityName:@"ConnexList"];
                    NSPredicate *predicate=[NSPredicate predicateWithFormat:@"id==%@",[NSString stringWithFormat:@"%@",[mutDic objectForKey:@"id"]]]; // If required to fetch specific user
                    fetchRequest.predicate=predicate;
                    NSArray *arr=[[self.managedObjectContext executeFetchRequest:fetchRequest error:nil] mutableCopy];
                    NSManagedObject *user=[arr lastObject];
                    if (user != nil)
                    {
                        [newObject setValue:(NSArray *)[user valueForKey:@"connexID"] forKey:@"connexID"];
                        [newObject setValue:@"YES" forKey:@"connexUser"];
                    }
                    else
                    {
                        
                        [newObject setValue:@"" forKey:@"connexID"];
                        [newObject setValue:@"YES" forKey:@"connexUser"];
                        @try
                        {
                            NSManagedObjectContext *managedObjectContext = [self managedObjectContext];
                            [managedObjectContext performBlock:^{
                                NSManagedObject *newObject = [NSEntityDescription insertNewObjectForEntityForName:@"ConnexList" inManagedObjectContext:managedObjectContext];
                                [newObject setValue:@"P" forKey:@"typeOfConnexUser"];
                                [newObject setValue:@"" forKey:@"id"];
                                [newObject setValue:@"" forKey:@"connexID"];
                                NSError *error = nil;
                                if ([managedObjectContext save:&error])
                                {   }
                            }];
                        } @catch (NSException *exception)
                        {   }
                        @finally
                        {   }
                    }
                }
                else
                {
                    [newObject setValue:@"" forKey:@"connexID"];
                    [newObject setValue:@"NO" forKey:@"connexUser"];
                }
                
                
                //        DebugLog(@"new data: %@",newDevice);
                
                NSError *error = nil;
                //Save the object to persistent store
                if (![managedObjectContext save:&error]) {
                    DebugLog(@"Can't Save! %@ %@", error, [error localizedDescription]);
                }
                
            }];
        }
        @catch (NSException *exception) {
            
        }
        @finally
        {
            
        }
    }
    dispatch_async(dispatch_get_main_queue(), ^{
        [[NSUserDefaults standardUserDefaults]setObject:@"SyncCompleted" forKey:IsSyncCompleted];
        [[NSNotificationCenter defaultCenter] postNotificationName:@"ContactsLoaded" object:Nil];
    });
}

- (void) resetCoreData
{
    NSManagedObjectContext *managedObjectContext = [self managedObjectContext];
    
    [ managedObjectContext performBlock:^{
        
        NSFetchRequest *fetchRequest = [[NSFetchRequest alloc] init];
        NSEntityDescription *entity = [NSEntityDescription entityForName:@"ContactList" inManagedObjectContext:managedObjectContext];
        [fetchRequest setEntity:entity];
        
        NSError *error;
        NSArray *items = [ managedObjectContext executeFetchRequest:fetchRequest error:&error];
        
        DebugLog(@"count of data %d",(int)items.count );
        
        for (NSManagedObject *managedObject in items) {
            [managedObjectContext deleteObject:managedObject];
            DebugLog(@"coredata object deleted");
        }
        if (![managedObjectContext save:&error]) {
            DebugLog(@"Error deleting coredata - error:%@",error);
        }
    }];
}

// contacts framework permission

-(void)getPermissionToUser
{
    NSLog(@"Get Permission to User");
    UIAlertController *alertController = [UIAlertController
                                          alertControllerWithTitle:@"Error!!!"
                                          message:[NSString stringWithFormat:@"You must enable contact permission to fetch all your contacts."]
                                          preferredStyle:UIAlertControllerStyleAlert];
    UIAlertAction *okAction = [UIAlertAction
                               actionWithTitle:NSLocalizedString(@"OK", @"OK action")
                               style:UIAlertActionStyleDefault
                               handler:^(UIAlertAction *action)
                               {
                                   NSLog(@"OK action");
                                   [alertController dismissViewControllerAnimated:YES completion:nil];
                               }];
    [alertController addAction:okAction];
    [navigation.visibleViewController presentViewController:alertController animated:YES completion:nil];
}

// fetching name and profile image from local db by phone number,if found returns name else phn number

-(NSString *)fetchName:(NSString *)phoneNumber
{
    profilePicLink=nil;
    NSString *name;
    
    NSManagedObject *user;
    NSFetchRequest *fetchRequest=[NSFetchRequest fetchRequestWithEntityName:@"ContactList"];
    NSArray *arr=[[self.managedObjectContext executeFetchRequest:fetchRequest error:nil] mutableCopy];
    
    for (int i=0 ; i<arr.count ; i++)
    {
        NSManagedObject *obj=[arr objectAtIndex:i];
        NSArray *phoneArray = [[obj valueForKey:@"phone"]mutableCopy];
        for (NSString *str in phoneArray)
        {
            if ([str rangeOfString:[NSString stringWithFormat:@"%@",phoneNumber]].location != NSNotFound)
            {
                user = obj;
                break;
            }
        }
    }
    
    if (user != nil || [user isKindOfClass:[NSNull class]])
    {
        name = [user valueForKey:@"name"];
    }
    else
        name = phoneNumber;
    
    NSString *searchFilename = [NSString stringWithFormat:@"pic%@.png",[user valueForKey:@"id"]]; // name of the file you are searching for
    
    NSArray *paths = NSSearchPathForDirectoriesInDomains(NSDocumentDirectory, NSUserDomainMask, YES);
    NSString *documentsDirectory = [paths objectAtIndex:0];
    NSDirectoryEnumerator *direnum = [[NSFileManager defaultManager] enumeratorAtPath:documentsDirectory];
    
    NSString *documentsSubpath;
    while (documentsSubpath = [direnum nextObject])
    {
        if (![documentsSubpath.lastPathComponent isEqual:searchFilename]) {
            continue;
        }
        profilePicLink = [NSString stringWithFormat:@"%@/%@",documentsDirectory,documentsSubpath];
    }
    return name;
}

@end
