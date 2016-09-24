//
//  ARTCVideoChatViewController.h
//  AppRTC
//
//  Created by Kelly Chu on 3/7/15.
//  Copyright (c) 2015 ISBX. All rights reserved.
//

#import <UIKit/UIKit.h>
#import "CNGlobalViewController.h"
#import <libjingle_peerconnection/RTCEAGLVideoView.h>
#import <AppRTC/ARDAppClient.h>
#import "AppDelegate.h"

@interface ARTCVideoChatViewController : CNGlobalViewController <ARDAppClientDelegate, RTCEAGLVideoViewDelegate,UIAlertViewDelegate>

//Views, Labels, and Buttons
@property (strong, nonatomic) IBOutlet RTCEAGLVideoView *remoteView;
@property (strong, nonatomic) IBOutlet RTCEAGLVideoView *localView;
@property (strong, nonatomic) IBOutlet UIView *footerView;
@property (strong, nonatomic) IBOutlet UILabel *urlLabel;
@property (strong, nonatomic) IBOutlet UIView *buttonContainerView;
@property (strong, nonatomic) IBOutlet UIButton *audioButton;
@property (strong, nonatomic) IBOutlet UIButton *videoButton;
@property (strong, nonatomic) IBOutlet UIButton *hangupButton;

//Auto Layout Constraints used for animations
@property (strong, nonatomic) IBOutlet NSLayoutConstraint *remoteViewTopConstraint;
@property (strong, nonatomic) IBOutlet NSLayoutConstraint *remoteViewRightConstraint;
@property (strong, nonatomic) IBOutlet NSLayoutConstraint *remoteViewLeftConstraint;
@property (strong, nonatomic) IBOutlet NSLayoutConstraint *remoteViewBottomConstraint;
@property (strong, nonatomic) IBOutlet NSLayoutConstraint *localViewWidthConstraint;
@property (strong, nonatomic) IBOutlet NSLayoutConstraint *localViewHeightConstraint;
@property (strong, nonatomic) IBOutlet NSLayoutConstraint *localViewRightConstraint;
@property (strong, nonatomic) IBOutlet NSLayoutConstraint *localViewBottomConstraint;
@property (strong, nonatomic) IBOutlet NSLayoutConstraint *footerViewBottomConstraint;
@property (strong, nonatomic) IBOutlet NSLayoutConstraint *buttonContainerViewLeftConstraint;

@property (strong, nonatomic) NSString *roomUrl;
@property (strong, nonatomic) NSString *roomName;
@property (strong, nonatomic) ARDAppClient *client;
@property (strong, nonatomic) RTCVideoTrack *localVideoTrack;
@property (strong, nonatomic) RTCVideoTrack *remoteVideoTrack;
@property (assign, nonatomic) CGSize localVideoSize;
@property (assign, nonatomic) CGSize remoteVideoSize;
@property (assign, nonatomic) BOOL isZoom; //used for double tap remote view

//toggle button parameter
@property (assign, nonatomic) BOOL isAudioMute;
@property (assign, nonatomic) BOOL isVideoMute;
@property (assign, nonatomic) BOOL isCameraPositionBack;

@property (retain, nonatomic)NSString *roomID;
@property (retain, nonatomic)NSString *receiverID;
@property (retain, nonatomic) NSString *userName;
@property (weak, nonatomic) IBOutlet UILabel *durationLBL;
@property (retain, nonatomic) IBOutlet UILabel *nameLBL;
@property (retain, nonatomic) IBOutlet UIImageView *chatImageV;
@property (retain, nonatomic) NSString *profileImageUrl;

@property (strong, nonatomic) IBOutlet NSLayoutConstraint *topContainerTopConstraint;
@property (strong, nonatomic) IBOutlet NSLayoutConstraint *bottomContainerBottomConstraint;

@property (retain, nonatomic) IBOutlet UIView *topView;
@property (retain, nonatomic) IBOutlet UIView *bottomView;

- (IBAction)audioButtonPressed:(id)sender;
- (IBAction)videoButtonPressed:(id)sender;
- (IBAction)hangupButtonPressed:(id)sender;

@property (retain,nonatomic)NSString *typeOfCall;

@property (retain, nonatomic)CTCallCenter *callCenter;

@end
