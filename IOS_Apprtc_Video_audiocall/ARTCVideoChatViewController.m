//
//  ARTCVideoChatViewController.m
//  AppRTC
//
//  Created by Kelly Chu on 3/7/15.
//  Copyright (c) 2015 ISBX. All rights reserved.
//

#import "ARTCVideoChatViewController.h"
#import <AVFoundation/AVFoundation.h>
#import "SVProgressHUD.h"

#import "CContactListViewController.h"
#import "CContactDetailsViewController.h"
#import <CoreTelephony/CTCall.h>
#import "Reachability.h"

#define SERVER_HOST_URL @"https://appr.tc"//@"https://apprtc.appspot.com" //app rtc default link
//#define SERVER_HOST_URL @"http://ec2-52-39-87-230.us-west-2.compute.amazonaws.com:8080"  //arnab da's team link
//#define SERVER_HOST_URL @"http://ec2-52-207-246-125.compute-1.amazonaws.com:8080" //swarup da's team link
@interface ARTCVideoChatViewController ()

@property (nonatomic) Reachability *hostReachability;
@end

@implementation ARTCVideoChatViewController
{
    UIView *toolView;
    BOOL isToolOpened,isOnCallWaiting;
    NSTimer *valTimer;
    int timeTaken;
    AppDelegate *appDel;
    int bottomContraintValue;
    UIDeviceOrientation previousOrientation;
    int conversationTime;
    UILabel *timeLBL;
    UIButton *videoBTN,*endBTN,*audioBTN;
    NSTimer *callTimer,*textTimer;
    NSString *oldCallState;
    UITapGestureRecognizer *tapGestureRecognizer;
    
}


- (void)viewDidLoad
{
    [super viewDidLoad];
    appDel=(AppDelegate *)[UIApplication sharedApplication].delegate;
    
    __weak ARTCVideoChatViewController * weakSelf = self;
    _callCenter = [[CTCallCenter alloc] init];
    oldCallState = CTCallStateDisconnected;
    _callCenter.callEventHandler=^(CTCall* call)
    {
        dispatch_async(dispatch_get_main_queue(), ^{
            [weakSelf sendHoldUnholdPushToUser:call];
        });
    };
    self.isZoom = NO;
    isToolOpened=NO;
    self.isAudioMute = NO;
    self.isVideoMute = NO;
    self.isCameraPositionBack=NO;
    //Add Tap to hide/show controls
    tapGestureRecognizer = [[UITapGestureRecognizer alloc] initWithTarget:self action:@selector(toggleButtonContainer)];
    [tapGestureRecognizer setNumberOfTapsRequired:1];
    [self.view addGestureRecognizer:tapGestureRecognizer];
    [tapGestureRecognizer setEnabled:NO];
    
    [self.remoteView setDelegate:self];
    [self.localView setDelegate:self];

    
    [UIDevice currentDevice].proximityMonitoringEnabled = YES;
    
    if ([UIDevice currentDevice].proximityMonitoringEnabled == YES)
    {
        [[NSNotificationCenter defaultCenter] addObserver:self
                                                 selector:@selector(proximityChanged:)
                                                     name:@"UIDeviceProximityStateDidChangeNotification"
                                                   object:[UIDevice currentDevice]];
    }
    
}

// call handler while receives any normal call during connex call and send push to remote.

-(void)sendHoldUnholdPushToUser:(CTCall *)call
{
    if(call.callState == CTCallStateDialing)
    {
        if (![oldCallState isEqualToString:CTCallStateDialing])
        {
            [self sendPushToUser:_receiverID withRoomID:_roomID withCallType:@"onhold" withStatus:@""];
            oldCallState = CTCallStateDialing;
        }
    }
    if(call.callState == CTCallStateIncoming)
    {
        if (![oldCallState isEqualToString:CTCallStateIncoming])
        {
            [self sendPushToUser:_receiverID withRoomID:_roomID withCallType:@"onhold" withStatus:@""];
            oldCallState = CTCallStateIncoming;
        }
    }
    
    if(call.callState == CTCallStateConnected)
    {
        NSLog(@"Call Connected");
        oldCallState = CTCallStateConnected;
    }
    
    if(call.callState == CTCallStateDisconnected)
    {
        if (![oldCallState isEqualToString:CTCallStateDisconnected])
        {
            oldCallState = CTCallStateDisconnected;
            [self sendPushToUser:_receiverID withRoomID:_roomID withCallType:@"unhold" withStatus:@""];
        }
        
    }
}

-(void)updateText
{
    if ([timeLBL.text isEqualToString:@"Connecting."]) {
        timeLBL.text = @"Connecting..";
        
    }else  if ([timeLBL.text isEqualToString:@"Connecting.."]) {
        timeLBL.text = @"Connecting...";
        
    }else if ([timeLBL.text isEqualToString:@"Connecting..."]) {
        timeLBL.text = @"Connecting";
    }else if ([timeLBL.text isEqualToString:@"Connecting"])
    {
        timeLBL.text = @"Connecting.";
    }
}

//  fired notification from ARDAppClient while remote user get connected. handling local rendering depending on meeting type. while meeting is voice only local video turned off and setting up timers.
-(void)peerConnectionGatheringStateChanged:(NSNotification *)notification
{
    dispatch_async(dispatch_get_main_queue(), ^{
        [[AVAudioSession sharedInstance] overrideOutputAudioPort:AVAudioSessionPortOverrideSpeaker error:nil];
        [callTimer invalidate];
        callTimer = nil;
        callTimer = [NSTimer scheduledTimerWithTimeInterval:1 target:self selector:@selector(updateTime) userInfo:nil repeats:YES];
        [textTimer invalidate];
        textTimer = nil;
        [endBTN setImage:[UIImage imageNamed:@"hangup"] forState:UIControlStateNormal];
        [endBTN setEnabled:YES];
    });
    NSLog(@"state change %@",notification.userInfo[@"state"]);
    if ([notification.userInfo[@"state"] isEqualToString:@"RTCICEConnectionConnected"])
    {
        [SVProgressHUD dismiss];
        if ([_typeOfCall isEqualToString:@"voiceaccept"])
        {
            [self.client muteVideoIn];
            self.isVideoMute = YES;
        }
        else
        {
            self.isVideoMute = NO;
        }
    }
}


- (void) proximityChanged:(NSNotification *)notification {
    UIDevice *device = [notification object];
    NSLog(@"In proximity: %i", device.proximityState);
    if (!self.isAudioMute)
    {
      if(device.proximityState == 0){
        [[AVAudioSession sharedInstance]  overrideOutputAudioPort:AVAudioSessionPortOverrideSpeaker error:nil];
       }
      else{
        [[AVAudioSession sharedInstance]  overrideOutputAudioPort:AVAudioSessionPortOverrideNone error:nil];
       }
    }
}


-(void)chatTap
{
    UIAlertController *alertController = [UIAlertController
                                          alertControllerWithTitle:@"Information!"
                                          message:[NSString stringWithFormat:@"Coming soon..."]
                                          preferredStyle:UIAlertControllerStyleAlert];
    UIAlertAction *okAction = [UIAlertAction
                               actionWithTitle:NSLocalizedString(@"OK", @"OK action")
                               style:UIAlertActionStyleCancel
                               handler:^(UIAlertAction *action)
                               {
                               }];
    [alertController addAction:okAction];
    [self presentViewController:alertController animated:YES completion:nil];
}

- (void)viewWillAppear:(BOOL)animated {
    [super viewWillAppear:animated];
    
    self.hostReachability = [Reachability reachabilityForInternetConnection];
    [self.hostReachability startNotifier];
    
    [[NSNotificationCenter defaultCenter]addObserver:self selector:@selector(hangupButtonPressed:) name:@"CallEndedByRemoteUser" object:nil];
    
    [[NSNotificationCenter defaultCenter] addObserver:self selector:@selector(peerConnectionGatheringStateChanged:) name:@"peerConnectionGatheringStateChanged" object:nil];
    
    [[NSNotificationCenter defaultCenter] addObserver:self selector:@selector(DidReceiveCallOnWaitingMode:) name:@"DidReceiveCallOnWaitingMode" object:nil];
    
    [[NSNotificationCenter defaultCenter] addObserver:self selector:@selector(holdUnholdHandler:) name:@"UserKeptOnHold" object:nil];
    
    [[NSNotificationCenter defaultCenter] addObserver:self selector:@selector(reachabilityChanged:) name:kReachabilityChangedNotification object:nil];
    
    [[self navigationController] setNavigationBarHidden:YES animated:YES];
    
    //Display the Local View full screen while connecting to Room
    [self.localViewBottomConstraint setConstant:0.0f];
    [self.localViewRightConstraint setConstant:0.0f];
    [self.localViewHeightConstraint setConstant:self.view.frame.size.height];
    [self.localViewWidthConstraint setConstant:self.view.frame.size.width];
    [self.footerViewBottomConstraint setConstant:0.0f];
    
    //Connect to the room
    
    [self makeConnection:_roomID];
}

- (void) reachabilityChanged:(NSNotification *)note
{
    Reachability* curReach = [note object];
    if (curReach == NotReachable)
    {
        
    }
}

// method for receiving notification while remote user receives any normal call.
-(void)holdUnholdHandler:(NSNotification *)notification
{
    NSString *msgString;
    if ([[notification.userInfo objectForKey:@"type"]isEqualToString:@"onhold"])
    {
        msgString = [NSString stringWithFormat:@"You have been kept on hold by %@",_userName];
        UIAlertController *alertController = [UIAlertController
                                              alertControllerWithTitle:@"Information!"
                                              message:msgString
                                              preferredStyle:UIAlertControllerStyleAlert];
        UIAlertAction *okAction = [UIAlertAction
                                   actionWithTitle:NSLocalizedString(@"OK", @"OK action")
                                   style:UIAlertActionStyleCancel
                                   handler:^(UIAlertAction *action)
                                   {
                                   }];
        [alertController addAction:okAction];
        [self.navigationController.visibleViewController presentViewController:alertController animated:YES completion:nil];
    }
}

// handling notification for accepting third call during another connex call.

-(void)DidReceiveCallOnWaitingMode:(NSNotification *)notification
{
    [appDel.conversationView removeFromSuperview];
    NSLog(@"notification user info %@",notification.userInfo);
    _roomID=[[notification.userInfo objectForKey:@"count_val"] objectForKey:@"roomId"];
    isOnCallWaiting=YES;
    NSString *callMode=[[notification.userInfo objectForKey:@"count_val"] objectForKey:@"mod"];
    if ([callMode isEqualToString:@"voice"])
        _typeOfCall=@"voiceaccept";
    else
        _typeOfCall=@"videoaccept";
    
    NSString *phoneNumber = [[notification.userInfo objectForKey:@"count_val"] objectForKey:@"phone"];
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
            }
        }
    }
    
    if (user != nil || [user isKindOfClass:[NSNull class]])
    {
        _userName = [user valueForKey:@"name"];
    }
    else
        _userName = phoneNumber;
    
    NSString *searchFilename = [NSString stringWithFormat:@"pic%@.png",[user valueForKey:@"id"]]; // name of the PDF you are searching for
    
    NSArray *paths = NSSearchPathForDirectoriesInDomains(NSDocumentDirectory, NSUserDomainMask, YES);
    NSString *documentsDirectory = [paths objectAtIndex:0];
    NSDirectoryEnumerator *direnum = [[NSFileManager defaultManager] enumeratorAtPath:documentsDirectory];
    
    NSString *documentsSubpath;
    while (documentsSubpath = [direnum nextObject])
    {
        if (![documentsSubpath.lastPathComponent isEqual:searchFilename]) {
            continue;
        }
        _profileImageUrl = [NSString stringWithFormat:@"%@/%@",documentsDirectory,documentsSubpath];
    }
    
    [self makeConnection:_roomID];
}

//method for creating meeting room depending on the meeting type
-(void)makeConnection:(NSString *)roomID
{
    NetworkStatus netStatus = [_hostReachability currentReachabilityStatus];
    if (netStatus != NotReachable)
    {
        [self disconnect];
        self.client = nil;
        self.client = [[ARDAppClient alloc] initWithDelegate:self];
        [self.client setServerHostUrl:SERVER_HOST_URL];
        _roomName = [NSString stringWithFormat:@"%@",roomID];
        [self setRoomName:_roomName];
        [self.client connectToRoomWithId:_roomName options:nil];
        
        self.isAudioMute = NO;
        self.isVideoMute = NO;
        
        [_nameLBL setText:[_userName capitalizedString]];
        
        if ([_typeOfCall isEqualToString:@"voiceaccept"])
        {
            appDel.conversationView=[[[NSBundle mainBundle]loadNibNamed:@"Dialler" owner:self options:nil]objectAtIndex:2];
            [appDel.conversationView setFrame:CGRectMake(0, 0, FULLWIDTH, FULLHEIGHT)];
            [self.view addSubview:appDel.conversationView];
            
            UILabel *nameLBL=(UILabel *)[appDel.conversationView viewWithTag:2];
            conversationTime = 0;
            
            UIImageView *backImage=(UIImageView *)[appDel.conversationView viewWithTag:1];
            [backImage setBackgroundColor:[UIColor clearColor]];
            if (_profileImageUrl != nil)
                [backImage setImage:[UIImage imageWithContentsOfFile:_profileImageUrl]];
            
            [nameLBL setText:[_userName capitalizedString]];
            timeLBL=(UILabel *)[appDel.conversationView viewWithTag:4];
            [timeLBL setText:@"Connecting"];
            
            UILabel *callTypeLBL = (UILabel *)[appDel.conversationView viewWithTag:3];
            [callTypeLBL setText:[NSString stringWithFormat:@"Connex %@ Call",[[_typeOfCall stringByReplacingOccurrencesOfString:@"accept" withString:@""] capitalizedString]]];
            
            
            endBTN=(UIButton *)[appDel.conversationView viewWithTag:8];
            audioBTN=(UIButton *)[appDel.conversationView viewWithTag:5];
            
            UIButton *chatBTN=(UIButton *)[appDel.conversationView viewWithTag:6];
            [chatBTN addTarget:self action:@selector(chatTap) forControlEvents:UIControlEventTouchUpInside];
            
            [videoBTN addTarget:self action:@selector(videoButtonPressed:) forControlEvents:UIControlEventTouchUpInside];
            [endBTN addTarget:self action:@selector(hangupButtonPressed:) forControlEvents:UIControlEventTouchUpInside];
            [audioBTN addTarget:self action:@selector(audioButtonPressed:) forControlEvents:UIControlEventTouchUpInside];
        }
        dispatch_async(dispatch_get_main_queue(), ^{
            [callTimer invalidate];
            callTimer=nil;
            callTimer = [NSTimer scheduledTimerWithTimeInterval:1 target:self selector:@selector(checkRemainingTime:) userInfo:nil repeats:YES];
            [textTimer invalidate];
            textTimer=nil;
            textTimer = [NSTimer scheduledTimerWithTimeInterval:.8 target:self selector:@selector(updateText) userInfo:nil repeats:YES];
        });
    }
    else
    {
        UIAlertController *alertController = [UIAlertController
                                              alertControllerWithTitle:@"Error!"
                                              message:[NSString stringWithFormat:@"Please check your internet connection."]
                                              preferredStyle:UIAlertControllerStyleAlert];
        UIAlertAction *okAction = [UIAlertAction
                                   actionWithTitle:NSLocalizedString(@"OK", @"OK action")
                                   style:UIAlertActionStyleCancel
                                   handler:^(UIAlertAction *action)
                                   {
                                       [self hangupButtonPressed:nil];
                                   }];
        [alertController addAction:okAction];
        [self.navigationController.visibleViewController presentViewController:alertController animated:YES completion:nil];
    }
}

-(void)viewDidAppear:(BOOL)animated
{
    [super viewDidAppear:YES];
    
    _topContainerTopConstraint.constant = -80;
    _bottomContainerBottomConstraint.constant = -80;
}


- (void)viewWillDisappear:(BOOL)animated {
    [super viewWillDisappear:animated];
    [[NSNotificationCenter defaultCenter]removeObserver:self];
    [self disconnect];
}

- (void)applicationWillResignActive:(UIApplication*)application {
    [self disconnect];
}

- (void)didReceiveMemoryWarning {
    [super didReceiveMemoryWarning];
}

//- (void)orientationChanged:(NSNotification *)notification{
//    [self videoView:self.localView didChangeVideoSize:self.localVideoSize];
//    [self videoView:self.remoteView didChangeVideoSize:self.remoteVideoSize];
//}

- (BOOL)prefersStatusBarHidden {
    return YES;
}

- (void)setRoomName:(NSString *)roomName {
    _roomName = roomName;
    self.roomUrl = [NSString stringWithFormat:@"%@/r/%@&hd=true&stereo=true&audio=echoCancellation=false&vsbr=50000", SERVER_HOST_URL, roomName];
    NSLog(@"room url : %@",_roomUrl);
}

- (void)disconnect {
    if (self.client) {
        if (self.localVideoTrack) [self.localVideoTrack removeRenderer:self.localView];
        if (self.remoteVideoTrack) [self.remoteVideoTrack removeRenderer:self.remoteView];
        self.localVideoTrack = nil;
        [self.localView renderFrame:nil];
        self.remoteVideoTrack = nil;
        [self.remoteView renderFrame:nil];
        [self.client disconnect];
    }
    [UIApplication sharedApplication].idleTimerDisabled = NO;

}

- (void)remoteDisconnected
{
    if (!isOnCallWaiting)
    {
        [self hangupButtonPressed:nil];
    }
    isOnCallWaiting=NO;
}

- (void)toggleButtonContainer {
    if (_topContainerTopConstraint.constant == -80)
    {
        isToolOpened=YES;
        UIDeviceOrientation orientation = [[UIDevice currentDevice] orientation];
        if (orientation == UIDeviceOrientationLandscapeLeft || orientation == UIDeviceOrientationLandscapeRight) {
            if (!_localVideoTrack)
            {
//                [_localViewBottomConstraint setActive:NO];
            }
            else
                [_localViewBottomConstraint setActive:YES];
        }
        else
        {
            //            if (!_localVideoTrack)//(_localVideoSize.width > 200)
            //               [_localViewBottomConstraint setActive:NO];
            //            else
            bottomContraintValue=_localViewBottomConstraint.constant;
        }
        [UIView animateWithDuration:0.3f animations:^{
            
            _topContainerTopConstraint.constant = 0;
            _bottomContainerBottomConstraint.constant = 0;
            if (_localView.frame.size.width < 200)
            {
                _localViewBottomConstraint.constant=0;
            }
            else
                _localViewBottomConstraint.constant=-80;
            [self.view layoutIfNeeded];
            [self performSelector:@selector(hideTools) withObject:nil afterDelay:3.0f];
        }];
        
    }
    else
    {
        //        [_buttonContainerView setUserInteractionEnabled:NO];
        //        [UIView animateWithDuration:0.3f animations:^{
        //            [toolView setFrame:CGRectMake(toolView.frame.origin.x, _buttonContainerView.frame.origin.y - 20, toolView.frame.size.width, 0)];
        //        } completion:^(BOOL finished) {
        //            if (finished)
        //            {
        //                [_buttonContainerView setUserInteractionEnabled:YES];
        isToolOpened=NO;
        //            }
        //        }];
        [UIView animateWithDuration:0.3f animations:^{
            _topContainerTopConstraint.constant = -80;
            _bottomContainerBottomConstraint.constant = -80;
            _localViewBottomConstraint.constant=bottomContraintValue;
            [self.view layoutIfNeeded];
        }];
        
    }
}

-(void)hideTools
{
    if (_topContainerTopConstraint.constant == 0)
    {
        isToolOpened=NO;
        [UIView animateWithDuration:0.3f animations:^{
            _topContainerTopConstraint.constant = -80;
            _bottomContainerBottomConstraint.constant = -80;
            _localViewBottomConstraint.constant=bottomContraintValue;
            [self.view layoutIfNeeded];
        }];
    }
}
- (IBAction)swapCamera:(id)sender
{
    if (self.isCameraPositionBack)
    {
        [self.client swapCameraToFront];
        self.isCameraPositionBack=NO;
    }
    else
    {
        [self.client swapCameraToBack];
        self.isCameraPositionBack=YES;
    }
}

- (IBAction)audioButtonPressed:(id)sender {
    //TODO: this change not work on simulator (it will crash)
    UIButton *audioButton = sender;
    if (self.isAudioMute) {
        [self.client unmuteAudioIn];
        [audioButton setImage:[UIImage imageNamed:@"audioOn"] forState:UIControlStateNormal];
        self.isAudioMute = NO;
    } else {
        [self.client muteAudioIn];
        [audioButton setImage:[UIImage imageNamed:@"audioOff"] forState:UIControlStateNormal];
        self.isAudioMute = YES;
    }
}

- (IBAction)videoButtonPressed:(id)sender {
    UIButton *videoButton = sender;
    if (self.isVideoMute) {
        [self.client unmuteVideoIn];
        [videoButton setImage:[UIImage imageNamed:@"videoOn"] forState:UIControlStateNormal];
        self.isVideoMute = NO;
    } else {
        [self.client muteVideoIn];
        [videoButton setImage:[UIImage imageNamed:@"videoOff"] forState:UIControlStateNormal];
        self.isVideoMute = YES;
    }
}


// method for ending that meeting and leaving that room
- (IBAction)hangupButtonPressed:(id)sender
{
    dispatch_async(dispatch_get_main_queue(), ^{
        if (sender == endBTN)
        {
            [self sendPushToUser:_receiverID withRoomID:_roomID withCallType:@"reject" withStatus:@""];
        }
        [SVProgressHUD dismiss];
        [textTimer invalidate];
        [callTimer invalidate];
        [self.client disconnect];
//        [self popToPreviousViewController];
        [UIApplication sharedApplication].idleTimerDisabled = NO;
        [[NSUserDefaults standardUserDefaults]setObject:connexCallStatusIdle forKey:connexCallStatus];
        if (appDel.userPhoneNumber == nil)
        {
            [self popToPreviousViewController:nil];
//            CContactListViewController *CCLVC=[[UIStoryboard storyboardWithName:@"IPhone" bundle:nil]instantiateViewControllerWithIdentifier:@"CContactListViewController"];
//            [self PushViewController:CCLVC WithAnimation:kCAMediaTimingFunctionEaseIn];
        }
        else
        {
            CContactDetailsViewController *CCDVC=[[UIStoryboard storyboardWithName:@"IPhone" bundle:nil]instantiateViewControllerWithIdentifier:@"CContactDetailsViewController"];
            CCDVC.userPhoneNumber=appDel.userPhoneNumber;
            [self PushViewController:CCDVC WithAnimation:kCAMediaTimingFunctionEaseIn];
        }
    });
}


#pragma mark - ARDAppClientDelegate

- (void)appClient:(ARDAppClient *)client didChangeState:(ARDAppClientState)state {
    switch (state) {
        case kARDAppClientStateConnected:
        {
            NSLog(@"self.client %@",self.client);
//            [self.client muteVideoIn];
        }
            break;
        case kARDAppClientStateConnecting:
            NSLog(@"Client connecting.");
            break; 
        case kARDAppClientStateDisconnected:
            NSLog(@"Client disconnected.");
            [self remoteDisconnected];
            break;
    }
}

- (void)appClient:(ARDAppClient *)client didReceiveLocalVideoTrack:(RTCVideoTrack *)localVideoTrack {
    if (self.localVideoTrack) {
        NSLog(@"didReceiveLocalVideoTrack22222222.");

        [self.localVideoTrack removeRenderer:self.localView];
        self.localVideoTrack = nil;
        [self.localView renderFrame:nil];
    }
    self.localVideoTrack = localVideoTrack;
    [self.localVideoTrack addRenderer:self.localView];
    [UIApplication sharedApplication].idleTimerDisabled = YES;

}

- (void)appClient:(ARDAppClient *)client didReceiveRemoteVideoTrack:(RTCVideoTrack *)remoteVideoTrack {
    [tapGestureRecognizer setEnabled:YES];
    NSLog(@"didReceiveRemoteVideoTrack3333333333");

    self.remoteVideoTrack = remoteVideoTrack;
    [self.remoteVideoTrack addRenderer:self.remoteView];
    [_localViewBottomConstraint setActive:YES];
    
    
    [UIView animateWithDuration:0.4f animations:^{
        //Instead of using 0.4 of screen size, we re-calculate the local view and keep our aspect ratio
        UIDeviceOrientation orientation = [[UIDevice currentDevice] orientation];
        CGRect videoRect = CGRectMake(0.0f, 0.0f, self.view.frame.size.width/4.0f, self.view.frame.size.height/4.0f);
        if (orientation == UIDeviceOrientationLandscapeLeft || orientation == UIDeviceOrientationLandscapeRight) {
            videoRect = CGRectMake(0.0f, 0.0f, self.view.frame.size.height/4.0f, self.view.frame.size.width/4.0f);
        }
        if (_localView.frame.size.height == 0)
        {
            CGRect tempRect;
            tempRect.size.height=(FULLHEIGHT - _localView.frame.origin.y);
            tempRect.size.width=(FULLWIDTH - _localView.frame.origin.x);
            _localView.frame = tempRect;
        }
        CGRect videoFrame = AVMakeRectWithAspectRatioInsideRect(_localView.frame.size, videoRect);
        
        [self.localViewWidthConstraint setConstant:videoFrame.size.width];
        [self.localViewHeightConstraint setConstant:videoFrame.size.height];
        [self.localViewBottomConstraint setConstant:0.0f];
        [self.localViewRightConstraint setConstant:0.0f];
        [self.view layoutIfNeeded];
    }];
    [UIApplication sharedApplication].idleTimerDisabled = YES;
}

- (void)appClient:(ARDAppClient *)client didError:(NSError *)error
{
    [tapGestureRecognizer setEnabled:YES];
    UIAlertController *alertController = [UIAlertController
                                          alertControllerWithTitle:@"Error!"
                                          message:[NSString stringWithFormat:@"Failed to join meeting"]
                                          preferredStyle:UIAlertControllerStyleAlert];
    UIAlertAction *okAction = [UIAlertAction
                               actionWithTitle:NSLocalizedString(@"OK", @"OK action")
                               style:UIAlertActionStyleCancel
                               handler:^(UIAlertAction *action)
                               {
                                   
                               }];
    [alertController addAction:okAction];
    [self presentViewController:alertController animated:YES completion:nil];
    [self disconnect];
    [UIApplication sharedApplication].idleTimerDisabled = NO;
}

#pragma mark - RTCEAGLVideoViewDelegate

- (void)videoView:(RTCEAGLVideoView *)videoView didChangeVideoSize:(CGSize)size
{
    UIDeviceOrientation orientation = [[UIDevice currentDevice] orientation];
   // [SVProgressHUD dismiss];
    [UIView animateWithDuration:0.4f animations:^{
        CGFloat containerWidth = self.view.frame.size.width;
        CGFloat containerHeight = self.view.frame.size.height;
        CGSize defaultAspectRatio = CGSizeMake(4, 3);
        if (videoView == self.localView) {
            //Resize the Local View depending if it is full screen or thumbnail
            self.localVideoSize = size;
            CGSize aspectRatio = CGSizeEqualToSize(size, CGSizeZero) ? defaultAspectRatio : size;
            CGRect videoRect = self.view.bounds;
            if (self.remoteVideoTrack) {
                videoRect = CGRectMake(0.0f, 0.0f, self.view.frame.size.width/4.0f, self.view.frame.size.height/4.0f);
                if (orientation == UIDeviceOrientationLandscapeLeft || orientation == UIDeviceOrientationLandscapeRight) {
                    videoRect = CGRectMake(0.0f, 0.0f, self.view.frame.size.height/4.0f, self.view.frame.size.width/4.0f);
                }
            }
            CGRect videoFrame = AVMakeRectWithAspectRatioInsideRect(aspectRatio, videoRect);
            
            //Resize the localView accordingly
            [self.localViewWidthConstraint setConstant:videoFrame.size.width];
            [self.localViewHeightConstraint setConstant:videoFrame.size.height];
            if (self.remoteVideoTrack) {
                [self.localViewBottomConstraint setConstant:28.0f]; //bottom right corner
                [self.localViewRightConstraint setConstant:28.0f];
            } else {
                [self.localViewBottomConstraint setConstant:containerHeight/2.0f - videoFrame.size.height/2.0f]; //center
                [self.localViewRightConstraint setConstant:containerWidth/2.0f - videoFrame.size.width/2.0f]; //center
            }
        } else if (videoView == self.remoteView) {
            //Resize Remote View
            self.remoteVideoSize = size;
            CGSize aspectRatio = CGSizeEqualToSize(size, CGSizeZero) ? defaultAspectRatio : size;
            CGRect videoRect = self.view.bounds;
            CGRect videoFrame = AVMakeRectWithAspectRatioInsideRect(aspectRatio, videoRect);
            if (self.isZoom) {
                //Set Aspect Fill
                CGFloat scale = MAX(containerWidth/videoFrame.size.width, containerHeight/videoFrame.size.height);
                videoFrame.size.width *= scale;
                videoFrame.size.height *= scale;
            }
            [self.remoteViewTopConstraint setConstant:containerHeight/2.0f - videoFrame.size.height/2.0f];
            [self.remoteViewBottomConstraint setConstant:containerHeight/2.0f - videoFrame.size.height/2.0f];
            [self.remoteViewLeftConstraint setConstant:containerWidth/2.0f - videoFrame.size.width/2.0f]; //center
            [self.remoteViewRightConstraint setConstant:containerWidth/2.0f - videoFrame.size.width/2.0f]; //center
            
        }
        [self.view layoutIfNeeded];
    }];
}


// 20 seconds to check weather remote user connected or not. after that it throws an alert an leave that meeting room
-(void)checkRemainingTime:(NSTimer *)timer
{
    timeTaken+=1;
    if (timeTaken == RINGINGTIME+5)
    {
        dispatch_async(dispatch_get_main_queue(), ^{
            [callTimer invalidate];
            callTimer = nil;
            [textTimer invalidate];
            timeLBL.text = @"Call failed";
            UIAlertController *alertController = [UIAlertController
                                                  alertControllerWithTitle:@"Error!"
                                                  message:[NSString stringWithFormat:@"Failed to connect call"]
                                                  preferredStyle:UIAlertControllerStyleAlert];
            UIAlertAction *okAction = [UIAlertAction
                                       actionWithTitle:NSLocalizedString(@"OK", @"OK action")
                                       style:UIAlertActionStyleDefault
                                       handler:^(UIAlertAction *action)
                                       {
                                           [self hangupButtonPressed:nil];
                                       }];
            [alertController addAction:okAction];
            [self presentViewController:alertController animated:YES completion:nil];
        });
    }
}

-(void)updateTime
{
    dispatch_async(dispatch_get_main_queue(), ^{
        conversationTime=conversationTime+1;
        NSString *time = [self timeFormatted:conversationTime];
        timeLBL.text=[NSString stringWithFormat:@"%@",time];
        _durationLBL.text=[NSString stringWithFormat:@"%@",time];
        _durationLBL.text=[NSString stringWithFormat:@"%@",time];
    });
}
- (NSString *)timeFormatted:(int)totalSeconds
{
    int seconds = totalSeconds % 60;
    int minutes = (totalSeconds / 60) % 60;
    return [NSString stringWithFormat:@"%02d:%02d",minutes, seconds];
}



@end